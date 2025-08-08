{{--<!DOCTYPE html>--}}
{{--<html lang="en">--}}
{{--<head>--}}
{{--    <meta charset="UTF-8" />--}}
{{--    <meta name="viewport" content="width=device-width, initial-scale=1.0" />--}}
{{--    <meta http-equiv="X-UA-Compatible" content="IE=edge" />--}}
{{--    <title>{{ $subject ?? 'EMAIL VERIFICATION' }}</title>--}}
{{--    <link rel="preconnect" href="https://fonts.googleapis.com" />--}}
{{--    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />--}}
{{--    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />--}}
{{--    <style>--}}
{{--        body, table, td, a { font-family: "Poppins", Arial, Helvetica, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }--}}
{{--        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }--}}
{{--        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; display: block; }--}}
{{--        body { margin: 0; padding: 0; width: 100% !important; background-color: #f4f4f4; }--}}
{{--        .container { max-width: 600px; width: 100%; background-color: #fff; margin: 0 auto; }--}}
{{--        @media only screen and (max-width: 600px) {--}}
{{--            .container { width: 100% !important; max-width: 100% !important; }--}}
{{--            .header-content-td { padding: 20px !important; }--}}
{{--            .footer-text, .content-text { font-size: 12px !important; }--}}
{{--        }--}}
{{--    </style>--}}
{{--</head>--}}
{{--<body>--}}
{{--<table width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">--}}
{{--    <tr>--}}
{{--        <td align="center">--}}
{{--            <table class="container" cellpadding="0" cellspacing="0" border="0">--}}
{{--                <!-- HEADER -->--}}
{{--                <tr>--}}
{{--                    <td class="header-bg" background="http://my.appollondigitals.com/public/img/header.jpg" style="background-color: #1e3a8a; background-image: url('http://my.appollondigitals.com/public/img/header.jpg'); background-size: cover; height: 160px;">--}}
{{--                        <table width="100%" cellpadding="0" cellspacing="0">--}}
{{--                            <tr><td height="20"></td></tr>--}}
{{--                            <tr>--}}
{{--                                <td class="header-content-td" style="padding: 50px 40px 20px 40px; color: #fff;">--}}
{{--                                    <h1 style="margin: 0; font-size: 24px; font-weight: 600;">--}}
{{--                                        {{ $heading ?? 'EMAIL VERIFICATION' }}--}}
{{--                                    </h1>--}}
{{--                                </td>--}}
{{--                            </tr>--}}
{{--                            <tr><td height="20"></td></tr>--}}
{{--                        </table>--}}
{{--                    </td>--}}
{{--                </tr>--}}

{{--                <!-- BODY -->--}}
{{--                <tr style="height: 300px">--}}
{{--                    <td style="padding: 30px 20px; background-color: #f5f5f5; vertical-align: top;">--}}
{{--                        <table width="100%" cellpadding="0" cellspacing="0">--}}
{{--                            <tr>--}}
{{--                                <td class="content-text" style="font-size: 14px; color: #333; line-height: 22px;">--}}
{{--                                    <b style="color: #798699; font-weight: bold">Dear Client,</b><br/><br/>--}}

{{--                                    <p>Your 6-digit verification code is: <strong>{{ $code }}</strong></p>--}}
{{--                                    <p>This code will expire in 15 minutes.</p>--}}

{{--                                    <span style="color: #798699; font-weight: bold">Sincerely<br/>Vortex</span>--}}
{{--                                </td>--}}
{{--                            </tr>--}}
{{--                        </table>--}}
{{--                    </td>--}}
{{--                </tr>--}}


{{--                <!-- FOOTER -->--}}
{{--                <tr>--}}
{{--                    <td style="background-color: #1e3a8a;">--}}
{{--                        <table width="100%" cellpadding="0" cellspacing="0" style="color: #fff;">--}}
{{--                            <tr>--}}
{{--                                <td class="footer-text" style="padding: 20px; font-size: 11px; line-height: 18px; text-align: center;">--}}
{{--                                    <strong style="font-size: 13px;">DISCLAIMER:</strong><br>--}}
{{--                                    Any views or opinions, unless otherwise specifically stated, are solely those of the author and do not--}}
{{--                                    necessarily represent those of Vortex, unless otherwise specifically stated. The information contained above is--}}
{{--                                    intended to provide general information and does not--}}
{{--                                    constitute or purports to be a financial or investment--}}
{{--                                    advice and is not intended to be presented as an offer--}}
{{--                                    or solicitation for the purchase or sale of any--}}
{{--                                    financial instrument. Client should seek personal--}}
{{--                                    professional advice before making any decisions.--}}
{{--                                </td>--}}
{{--                            </tr>--}}
{{--                            <tr>--}}
{{--                                <td align="center" style="background-color: #123c7b; font-size: 12px; padding: 15px 0;">--}}
{{--                                    © Vortex {{ now()->year }}. All Rights Reserved.--}}
{{--                                </td>--}}
{{--                            </tr>--}}
{{--                        </table>--}}
{{--                    </td>--}}
{{--                </tr>--}}

{{--            </table>--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--</table>--}}
{{--</body>--}}
{{--</html>--}}


@extends('emails.layout')

@section('content')
    @php
        $heading = 'Email Verification Code';
        $body = "Your 6-digit verification code is <strong>{$code}</strong>,<br>This code will expire in 15 minutes";
    @endphp
@endsection

