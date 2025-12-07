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
        <div><textarea spellcheck="false" style="width: 100%; border: none; font-family: 'DejaVu Sans', sans-serif; font-size: 16px; line-height: 1.2; resize: none; outline: none; background: #00000012; overflow: hidden;">{{$sotrudnik->full_name}} берілді.
Себебі ол "ӨзенМұнайСервис" жауапкершілігі шектеулі серіктестігінде  {{$sotrudnik->position->name_kz}} болып жұмыс жасайды.
Анықтама талап етілген жеріне берілді берілді.</textarea></div>
    </div>

    <div class="signatory">
        <div class="left">
            <p><strong><textarea id="signer.position" name="signer_position" style="border: none; width: 100%; resize: none; font-weight: bold; font-size: 16px; font-family: 'DejaVu Sans', sans-serif; outline: none; background: #00000012; line-height: 1.2; overflow: hidden;" oninput="updatePreview()">{{$spravka->signer->position}}</textarea></strong></p>
        </div>
        <div class="right">
            <strong><p><input type="text" id="signer.fio" name="signer_fio" style="border: none; width: 100%; font-weight: bold; font-size: 16px; font-family: 'DejaVu Sans', sans-serif; outline: none; background: #00000012;" value="{{$spravka->signer->last_name . ' ' . mb_substr($spravka->signer->first_name, 0, 1) . '. ' . mb_substr($spravka->signer->father_name, 0, 1) . '.'}}" oninput="updatePreview()"></p></strong>
        </div>
    </div>

</div>
</div>

<form id="preview-form"  action="{{ route('generate.pdf') }}" method="POST"  data-controller="" data-action="" novalidate>
    @csrf
    <input type="hidden" id="text_kz_to_pdf" name="text_kz_to_pdf">
    <input type="hidden" id="signer_position_to_pdf" name="signer_position">
    <input type="hidden" id="signer_fio_to_pdf" name="signer_fio">
    <input type="hidden" id="id_spravka" name="id_spravka" value="{{$spravka->id ?? ''}}">
    <div style="text-align: center; margin: 40px auto; max-width: 21cm;">
        <button type="submit" class="btn btn-primary" id="generate-pdf-button" style="font-size: 18px; padding: 15px 50px; font-weight: bold; border-radius: 8px;">Генерировать PDF</button>
    </div>
</form>

<script>
    // Функция автоматического изменения высоты textarea
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {

        var route_generate = '{{ route('generate.pdf') }}';

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

        const form = document.getElementById('post-form'); // ID формы, который автоматически добавляет Orchid
        if (form) {
            form.action = route_generate;
        }

        window.addEventListener("beforeunload", function(event) {
            window.onbeforeunload = null;
            event.preventDefault();
            event.returnValue = null;
            return null;
        });

        // Добавляем обработчики для input полей
        const inputFields = document.querySelectorAll('input[type="text"]');
        inputFields.forEach(field => {
            field.addEventListener('input', updatePreview);
        });

        // Обработчик отправки формы
        const formElement = document.getElementById('post-form');
        if (formElement) {
            formElement.addEventListener('submit', function(e) {
                updatePreview();
            });
        }
    });

    function updatePreview() {
        const form = document.getElementById('post-form');
        if (form) {
            form.action = "{{ route('generate.pdf') }}";
        }

        const textareas = document.querySelectorAll('textarea[spellcheck="false"]');
        const textKz = textareas[0] ? textareas[0].value : '';

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
