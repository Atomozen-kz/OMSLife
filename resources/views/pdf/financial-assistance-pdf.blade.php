<!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявка на материальную помощь</title>
    <style>
        .form_pdf {
            font-family: "DejaVu Sans", sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 92%;
            margin: 4%;
            padding-top: 30px;
        }
        .header, .footer {
            text-align: center;
            font-size: 13px;
        }
        .content {
            font-size: 14px;
            line-height: 1.4;
            margin-top: 20px;
        }
        .form-field {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #fafafa;
        }
        .field-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .field-value {
            min-height: 20px;
            border-bottom: 1px dotted #999;
            padding-bottom: 5px;
        }
        .signatory {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .left {
            float: left;
            font-size: 14px;
        }
        .right {
            float: right;
        }
        .signatory div {
            width: 45%;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td {
            padding: 5px;
            vertical-align: top;
        }
        .attachments {
            margin-top: 30px;
        }
        .attachment-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container form_pdf">
    {{-- Header --}}
    @include('pdf.financial-assistance-header', [
        'sotrudnik' => $sotrudnik ?? null,
        'assistance_type' => $assistanceType ?? null,
        'current_date' => $currentDate ?? date('d.m.Y'),
        'department' => $sotrudnik->organization->name_ru ?? null,
        'request_id' => $request->id ?? null,
    ])

    {{-- Content --}}
    <div class="content">
        {!! $contentHtml ?? '<p>Содержимое не найдено</p>' !!}

{{--        @if(isset($formData) && !empty($formData))--}}
{{--            <div style="margin-top: 30px;">--}}
{{--                <h3>Данные заявки:</h3>--}}
{{--                @foreach($formData as $fieldName => $fieldValue)--}}
{{--                    <div class="form-field">--}}
{{--                        <div class="field-label">{{ $fieldName }}:</div>--}}
{{--                        <div class="field-value">{{ $fieldValue }}</div>--}}
{{--                    </div>--}}
{{--                @endforeach--}}
{{--            </div>--}}
{{--        @endif--}}
    </div>

    {{-- Footer --}}
    @include('pdf.financial-assistance-footer', [
        'sotrudnik' => $sotrudnik ?? null,
        'signer' => $signer ?? null,
        'current_date' => $currentDate ?? date('d.m.Y'),
        'processed_date' => isset($request) && $request->processed_at ? $request->processed_at->format('d.m.Y') : null,
        'request_id' => $request->id ?? null,
    ])
</div>

</body>
</html>
