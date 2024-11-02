<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Настройки модуля</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1140px;
            margin-top: 50px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #343a40;
            margin-bottom: 30px;
        }

        .checkbox-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px; /* Отступ снизу для кнопок */
        }

        /* Стили для эффекта загрузки */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Затемнение фона */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Поверх остальных элементов */
            display: none;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* Стили для модального окна */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            min-width: 400px; /* Увеличен размер окна */
            opacity: 0; /* Начальная непрозрачность */
            transition: opacity 0.5s ease; /* Плавный переход */
        }
        .toast.show {
            opacity: 1; /* Полная непрозрачность при показе */
        }
        .toast-header {
            background-color: #28a745; /* Зеленый цвет заголовка */
            color: white; /* Белый текст для заголовка */
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Настройки модуля</h1>
    <div class="form-group">
        <div class="custom-control custom-switch">
            <input type="checkbox" {{ $active == true ? 'checked' : '' }} class="custom-control-input"
                   id="toggleSwitch">
            <label class="custom-control-label" for="toggleSwitch">Включен</label>
        </div>
    </div>
    <form class="form-group" method="post" action="/settings-save">
        @csrf
        <label for="selectFields">Выберите поля для обновления:</label>
        <div class="checkbox-list" id="selectFields">
            @foreach ($update_inputs as $u_input)

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input company_inputs"
                           {{ $u_input['selected'] == true ? 'checked' : '' }} id="{{ $u_input['externalId'] }}"
                           value="{{ $u_input['externalId'] }}">
                    <label class="custom-control-label"
                           for="{{ $u_input['externalId'] }}">{{ $u_input['name'] }}</label>
                </div>

            @endforeach
        </div>
        <button type="button" class="btn btn-success" id="saveButton">Сохранить</button>
    </form>
</div>
<div class="container">
    <h4>Обновление</h4>
    <div class="form-group d-flex flex-column">
        <span class="mb-3">Запускает обновления полей по всем текущим компаниям</span>
        <button type="button" class="btn btn-warning align-self-start" id="updateButton">Запустить обновление</button>
    </div>
</div>
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-light" role="status">
        <span class="sr-only">Загрузка...</span>
    </div>
</div>
<div class="toast" id="successToast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
        <strong class="mr-auto">Успешно сохранено!</strong>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Закрыть">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="toast-body">
        Запись была успешно сохранена.
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        $('#saveButton').click(function () {
            $('#loadingOverlay').css('display', 'flex');
            const isActive = $('#toggleSwitch').is(':checked') ? true : false;

            const selectedInputs = $('.company_inputs[type="checkbox"]:checked').map(function () {
                return $(this).val();
            }).get();

            const data = {
                id: <?= $id ?>,
                active: isActive,
                selectedInputs: selectedInputs
            };
            console.log(data);

            $.ajax({
                url: '/settings-save',
                type: 'POST',
                async: true,
                contentType: 'application/json',
                data: JSON.stringify(data),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    $('#loadingOverlay').css('display', 'none');
                    var toast = $('#successToast');
                    toast.addClass('show');
                    setTimeout(function() {
                        toast.removeClass('show');
                    }, 3000);
                    console.log('Данные успешно отправлены:', response);
                },
                error: function (xhr, status, error) {
                    $('#loadingOverlay').css('display', 'none');
                    console.error('Ошибка при отправке данных:', status, error);
                }
            })
        });


        $('#updateButton').click(function() {
            $.ajax({
                url: '/update-companies',
                type: 'POST',
                async: true,
                contentType: 'application/json',
                data: <?= $id ?>,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    $('#loadingOverlay').css('display', 'none');
                    var toast = $('#successToast');
                    toast.addClass('show');
                    setTimeout(function() {
                        toast.removeClass('show');
                    }, 3000);
                    console.log('Данные успешно отправлены:', response);
                },
                error: function (xhr, status, error) {
                    $('#loadingOverlay').css('display', 'none');
                    console.error('Ошибка при отправке данных:', status, error);
                }
            })
        });
    });
</script>
</body>
</html>
