<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>@yield('title')</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.55;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        .gray-bg {
            background-color: #f3f4f6;
        }

        .container-td {
            padding: 0 16px;
            max-width: 600px;
        }

        .container-div {
            padding: 0;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table td {
            border: 2px solid white;
        }

        .logo-td {
            padding: 28px 0 8px;
        }

        .logo {
            max-height: 55px;
        }

        .inner-div {
            padding: 0 28px 8px;
        }

        .gray-highlighting {
            padding: 16px 28px;
        }

        .otp {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 6px;
            color: #111111;
            background-color: #fff8e1;
            border: 1px solid #ffc800;
            border-radius: 10px;
            display: inline-block;
        }

        .summary-box {
            background-color: #fafafa;
            border-left: 4px solid #ffc800;
            border-radius: 8px;
            padding: 16px 20px;
            text-align: left;
            line-height: 1.65;
        }

        .action-button {
            display: inline-block;
            margin-top: 8px;
            margin-bottom: 8px;
            padding: 12px 22px;
            background-color: #ffc800;
            text-decoration: none;
            color: #000000;
            border: 1px solid orange;
            border-radius: 8px;
            font-weight: 700;
        }

        .spacer {
            height: 32px;
        }

        h1,
        h2,
        h3 {
            font-weight: 700;
            color: #111111;
        }

        h1 {
            font-size: 22px;
            margin: 8px 0;
        }

        h2 {
            font-size: 16px;
            margin: 8px 0;
        }
    </style>
</head>

<body class="gray-bg">
    <div align="center">
        @yield('content')
    </div>
</body>

</html>
