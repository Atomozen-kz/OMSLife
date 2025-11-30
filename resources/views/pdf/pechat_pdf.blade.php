<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .seal {
            position: absolute;
            bottom: 20px;
            right: 20px;
            padding: 10px;
            border: 1px solid blue;
            color: blue;
            font-size: 10px;
            width: 200px;
            text-align: left;
            line-height: 1.2;
        }
    </style>
</head>
<body>
<div class="seal">
    {!! $sealText !!}
</div>
<img src="{{ $imageBase64 }}" style="position:absolute; bottom:20px; left:20px; width:150px;" />
</body>
</html>
