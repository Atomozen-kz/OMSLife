<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справка</title>
    <style>
        {{--@font-face {--}}
        {{--    font-family: 'Times New Roman';--}}
        {{--    src: url({{ storage_path('fonts\Times-New-Roman.ttf') }}) format("truetype");--}}
        {{--    font-weight: 400; // use the matching font-weight here ( 100, 200, 300, 400, etc).--}}
        {{--    font-style: normal; // use the matching font-style here--}}
        {{--}--}}
        {{--@font-face {--}}
        {{--    font-family: 'Times New Roman Bold';--}}
        {{--    src: url({{ storage_path('fonts\Times-New-Roman-Bold.ttf') }}) format("truetype");--}}
        {{--    font-weight: 400; // use the matching font-weight here ( 100, 200, 300, 400, etc).--}}
        {{--    font-style: normal; // use the matching font-style here--}}
        {{--}--}}
        .form_pdf {
            font-family: "DejaVu Sans", sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 92%;
            margin:4%;
            padding-top: 30px;
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

<div class="container form_pdf">
    <div class="header">
        <table width="100%">
            <tr>
                <td align="center"><p><strong>«Өзенмұнайгаз» акционерлік қоғамы</strong></p></td>
                <td  align="center">
                    <img src="{{'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('storage/omg_logo.jpg')))}}" alt="Логотип" width="200"/>
                </td>
                <td  align="center"><p><strong>Акционерное общество «Озенмунайгаз»</strong></p></td>
            </tr>
        </table>
    </div>

    <div class="content text-center">
        <h2><center>Анықтама/Справка</center></h2>
        <p>{{$todayDate}}<br>Жаңаөзен қаласы</p>
    </div>
    <div class="content">
        <table>
            <tr>
                <td valign="top" style="padding-right: 15px;">
                    <p><strong>Берілді:</strong>

                        <span id="fio_kz">{{ $sotrudnik->last_name }} {{ $sotrudnik->first_name }} {{ $sotrudnik->father_name }}</span>,

                        <span id="text_kz" style="text-align: justify; display: block;">{{ $text_kz }}</span>
                    </p>

                </td>
                <td valign="top" style="padding-left: 15px;">
                    <p><strong>Выдана:</strong>

                        <span id="fio_ru">{{ $sotrudnik->last_name }} {{ $sotrudnik->first_name }} {{ $sotrudnik->father_name }}</span>

                        <span id="text_ru" style="text-align: justify; display: block;">{{ $text_ru }}</span></p>

                </td>
            </tr>
        </table>
    </div>

    <div class="signatory">
        <div class="left">
            <p><strong id="signer_position"> {{ $signer->position }} </strong></p>
        </div>
        <div class="right">
            <strong><p id="signer_fio"> {{ $signer->fio }}  </p></strong>
        </div>
    </div>

{{--    <div class="footer" style="  position: relative;  margin-top: 450px;">--}}
{{--        <div style="font-size: 7px; width: 80%;">--}}
{{--            <p style="    margin-bottom: 0px;">Осы құжат «Электрондық құжат және электрондық цифрлық қолтаңба туралы» Қазақстан Республикасының 2003 жылғы 7 қаңтардағы N 370-II Заңы 7 бабының 1 тармағына сәйкес қағаз тасығыштағы құжатпен бірдей</p>--}}
{{--            <p>Данный документ согласно пункту 1 статьи 7 ЗРК от 7 января 2003 года N370-II «Об электронном документе и электронной цифровой подписи» равнозначен документу на бумажном носителе</p>--}}
{{--        </div>--}}
{{--        <div class="qrcode" style="    position: absolute;    right: 0px;">--}}
{{--            <img src="{{'data:image/jpeg;base64,'.base64_encode(file_get_contents('https://api.qrserver.com/v1/create-qr-code/?data='.env('APP_URL').'/spravka_proverka/'.$spravka->id.'&size=75x75'))}}" alt="" title="" />--}}
{{--        </div>--}}
{{--    </div>--}}
</div>

</body>
</html>
