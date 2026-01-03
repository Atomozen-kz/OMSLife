<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справка</title>
    <style>
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
            <tr style="vertical-align: middle;">
                <td align="center">«ӨзенМұнайСервис» жауапкершілігі шектеулі серіктестігі</td>
                <td  align="center" style="    padding: 0px 15px;">
                    <img src="{{'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('storage/logo-width.jpg')))}}" alt="Логотип" width="200"/>
                </td>
                <td  align="center">Товарищество с ограниченной ответственностью «ОзенМунайСервис»</td>
            </tr>
        </table>
    </div>

    <div class="content text-center">
        <h2><center>Анықтама/Справка</center></h2>
        <p>{{$todayDate}}<br>Жаңаөзен қаласы</p>
    </div>
    <div class="content">

        <div>{!! nl2br(e($text_kz)) !!}</div>

    </div>

    <div class="signatory">
        <div class="left">
            <p><strong id="signer_position"> {{ $signer->position }} </strong></p>
        </div>
        <div class="right">
            <strong><p id="signer_fio"> {{ $signer->fio }}  </p></strong>
        </div>
    </div>
</div>

</body>
</html>
