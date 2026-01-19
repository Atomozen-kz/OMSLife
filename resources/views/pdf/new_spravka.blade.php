<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справка</title>
    <style>
        /*body {*/
        /*    background-color: #f5f5f5;*/
        /*    padding: 20px 0;*/
        /*    margin: 0;*/
        /*}*/

        .form_pdf {
            font-family: "DejaVu Sans", sans-serif;
            margin: 0 auto;
            padding: 0;
            max-width: 21cm; /* A4 ширина */
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .container {
            width: 100%;
            padding: 2cm 2cm; /* A4 отступы */
            box-sizing: border-box;
            min-height: 18cm;
        }

        .header, .footer {
            text-align: center;
            font-size: 14px;
        }

        .content {
            font-size: 14px;
            line-height: 1.2;
            margin-top: 20px;
        }

        .signatory {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .left{
            float: left;
            font-size: 14px;
        }

        .right{
            float: right;
        }

        .signatory div {
            width: 45%;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
<div class="form_pdf">
<div class="container">
    <div class="header">
        <table width="100%">
            <tr style="vertical-align: middle;">
                <td align="center">«ӨзенМұнайСервис» жауапкершілігі шектеулі серіктестігі</td>
                <td  align="center" style="    padding: 0px 15px;">
                    <img src="{{'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('storage/logo-width.jpg')))}}" alt="Логотип" width="200"/>
                </td>
                <td  align="center">Товарищество с ограниченной ответственностью «ОзенМунайСервис»</td>
            </tr>
        </table>
    </div>

    <div class="content text-center" style="margin-top: 50px">
        <center><h4 style="font-weight: bold;">Анықтама</h4></center>
        <p><center>{{$todayDate}}<br>Жаңаөзен қаласы</center></p>
    </div>
    <div class="content">
        <div><textarea id="text_kz_textarea" spellcheck="false" oninput="updatePreview()" style="width: 100%; min-height: 150px; border: none; font-family: 'DejaVu Sans', sans-serif; font-size: 16px; line-height: 1.2;  outline: none; background: #00000012; overflow: hidden;">{{$sotrudnik->full_name}} берілді.
Себебі ол "ӨзенМұнайСервис" жауапкершілігі шектеулі серіктестігінде  {{$sotrudnik->position->name_kz}} болып жұмыс жасайды.
Анықтама талап етілген жеріне берілді.</textarea></div>
    </div>

    <div class="signatory">
        <div class="left">
            <p><strong><textarea id="signer.position" style="border: none; width: 100%; resize: none; font-weight: bold; font-size: 16px; font-family: 'DejaVu Sans', sans-serif; outline: none; background: #00000012; line-height: 1.2; overflow: hidden;" oninput="updatePreview()">{{$spravka->signer->position}}</textarea></strong></p>
        </div>
        <div class="right">
            <strong><p><input type="text" id="signer.fio" style="border: none; width: 100%; font-weight: bold; font-size: 16px; font-family: 'DejaVu Sans', sans-serif; outline: none; background: #00000012;" value="{{$spravka->signer->last_name . ' ' . mb_substr($spravka->signer->first_name, 0, 1) . '. ' . mb_substr($spravka->signer->father_name, 0, 1) . '.'}}" oninput="updatePreview()"></p></strong>
        </div>
    </div>

</div>
</div>

<!-- Скрытые поля для хранения данных (вне формы) -->
<input type="hidden" id="text_kz_to_pdf">
<input type="hidden" id="signer_position_to_pdf">
<input type="hidden" id="signer_fio_to_pdf">
<input type="hidden" id="id_spravka" value="{{$spravka->id ?? ''}}">
<input type="hidden" id="generate_pdf_url" value="{{ route('generate.pdf') }}">

<div style="text-align: center; margin: 40px auto; max-width: 21cm;">
    <button type="button" class="btn btn-primary" id="generate-pdf-button" onclick="return submitPdfForm(event)" style="font-size: 18px; padding: 15px 50px; font-weight: bold; border-radius: 8px;">Генерировать PDF</button>
</div>

<script>
    function submitPdfForm(event) {
        // Останавливаем любые другие обработчики
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Отключаем проверку изменений формы Orchid
        window.onbeforeunload = null;

        // Убираем атрибут, который отслеживает изменения в форме Orchid
        var orchidForm = document.getElementById('post-form');
        if (orchidForm) {
            orchidForm.setAttribute('data-form-need-prevents-form-abandonment-value', 'false');
        }

        // Создаём новую форму вне Orchid контекста
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = document.getElementById('generate_pdf_url').value;
        form.style.display = 'none';
        form.setAttribute('data-turbo', 'false');

        // Добавляем CSRF токен (берём актуальный из meta-тега или из формы Orchid)
        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';

        // Пробуем несколько источников токена
        var csrfToken = null;

        // 1. Из meta-тега
        var metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            csrfToken = metaToken.getAttribute('content');
        }

        // 2. Из формы Orchid post-form
        if (!csrfToken) {
            var existingOrchidForm = document.getElementById('post-form');
            if (existingOrchidForm) {
                var orchidCsrf = existingOrchidForm.querySelector('input[name="_token"]');
                if (orchidCsrf) {
                    csrfToken = orchidCsrf.value;
                }
            }
        }

        // 3. Из любой формы на странице
        if (!csrfToken) {
            var anyTokenInput = document.querySelector('input[name="_token"]');
            if (anyTokenInput) {
                csrfToken = anyTokenInput.value;
            }
        }

        csrf.value = csrfToken || '';
        form.appendChild(csrf);

        // Берём данные напрямую из полей ввода (не из скрытых полей)
        var textKzTextarea = document.getElementById('text_kz_textarea');
        var signerPositionTextarea = document.getElementById('signer.position');
        var signerFioInput = document.getElementById('signer.fio');

        // Отладка - вывод в консоль
        console.log('=== SUBMIT PDF FORM ===');
        console.log('Text KZ:', textKzTextarea ? textKzTextarea.value : 'NOT FOUND');
        console.log('Signer Position:', signerPositionTextarea ? signerPositionTextarea.value : 'NOT FOUND');
        console.log('Signer FIO:', signerFioInput ? signerFioInput.value : 'NOT FOUND');

        var fields = {
            'text_kz_to_pdf': textKzTextarea ? textKzTextarea.value : '',
            'signer_position': signerPositionTextarea ? signerPositionTextarea.value : '',
            'signer_fio': signerFioInput ? signerFioInput.value : '',
            'id_spravka': document.getElementById('id_spravka').value
        };

        console.log('Fields to submit:', fields);

        for (var name in fields) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = fields[name];
            form.appendChild(input);
        }

        // Добавляем форму к body (вне post-form) и отправляем
        document.body.appendChild(form);

        console.log('Submitting form to:', form.action);
        form.submit();

        return false;
    }
</script>

<script>
    // Функция автоматического изменения высоты textarea
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    // Инициализация при загрузке страницы
    function initPage() {
        updatePreview();

        // Находим все textarea и устанавливаем начальную высоту
        const allTextareas = document.querySelectorAll('textarea');
        allTextareas.forEach(textarea => {
            autoResize(textarea);

            // Добавляем обработчик для автоматического изменения высоты при вводе
            textarea.addEventListener('input', function() {
                autoResize(this);
                updatePreview();
            });
        });


        // Добавляем обработчики для input полей
        const inputFields = document.querySelectorAll('input[type="text"]');
        inputFields.forEach(field => {
            field.addEventListener('input', updatePreview);
            // Предотвращаем отправку формы Orchid при нажатии Enter
            field.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });

        // Предотвращаем отправку формы Orchid post-form
        const orchidForm = document.getElementById('post-form');
        if (orchidForm) {
            orchidForm.addEventListener('submit', function(e) {
                // Если это не наша кнопка, отменяем отправку
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
        }
    }

    // Инициализация при загрузке страницы (обычная загрузка)
    document.addEventListener('DOMContentLoaded', initPage);

    // Инициализация при навигации через Turbo (для Orchid)
    document.addEventListener('turbo:load', initPage);
    document.addEventListener('turbo:render', initPage);

    function updatePreview() {

        const textKzTextarea = document.getElementById('text_kz_textarea');
        const textKz = textKzTextarea ? textKzTextarea.value : '';

        const signerPosition = document.getElementById('signer.position') ? document.getElementById('signer.position').value : '';
        const signerFio = document.getElementById('signer.fio') ? document.getElementById('signer.fio').value : '';

        // Обновляем скрытые поля
        document.getElementById('text_kz_to_pdf').value = textKz;
        document.getElementById('signer_position_to_pdf').value = signerPosition;
        document.getElementById('signer_fio_to_pdf').value = signerFio;
    }
</script>

</body>
</html>
