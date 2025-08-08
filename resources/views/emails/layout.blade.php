<!DOCTYPE html>
<html
    lang="en"
    xmlns="http://www.w3.org/1999/xhtml"
    xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office"
>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <meta name="x-apple-disable-message-reformatting"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="color-scheme" content="light dark"/>
    <meta name="supported-color-schemes" content="light dark"/>
    <title>{{ $subject ?? 'Notification' }}</title>
    <!--[if mso]>
    <style>
        table,
        td,
        div,
        p,
        a {
            font-family: "Poppins", sans-serif !important;
        }
    </style>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap");

        table,
        td,
        div,
        h1,
        p {
            font-family: "Poppins", sans-serif;
        }

        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }

        @media (prefers-color-scheme: dark) {
            .dark-bg {
                background-color: black !important;
            }

            .dark-text {
                color: #F2FCFE !important;
            }

            .dark-subtext {
                color: #cccccc !important;
            }

            .light-mode-banner {
                display: none !important;
            }

            .dark-mode-banner {
                display: block !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; word-spacing: normal">
<table
    role="presentation"
    style="
        width: 100%;
        border-collapse: collapse;
        border: 0;
        border-spacing: 0;
        background: black;
      "
>
    <tr>
        <td align="center" style="padding: 0">
            <table
                role="presentation"
                style="
              width: 100%;
              max-width: 602px;
              border-collapse: collapse;
              border-spacing: 0;
              text-align: left;
            "
            >
                <tr>
                    <td align="center">
                        <img
                            src="https://cards.tradexcellence.co.uk/backend/public/img/banner_trade.jpg"
                            alt="The BlackDuck Card - Spend Crypto Anywhere With Ease"
                            width="602"
                            class="light-mode-banner"
                            style="
                    height: auto;
                    display: block;
                    width: 100%;
                    max-width: 602px;
                  "
                        />
                        <!--[if !mso]><!-->
                        <img
                            src="http://cards.tradexcellence.co.uk/backend/public/img/banner_trade.jpg"
                            alt="The BlackDuck Card - Spend Crypto Anywhere With Ease"
                            width="602"
                            class="dark-mode-banner"
                            style="
                    display: none;
                    height: auto;
                    width: 100%;
                    max-width: 602px;
                  "
                        />
                        <!--<![endif]-->
                    </td>
                </tr>

                <tr>
                    <td
                        class="dark-bg"
                        style="padding: 36px 30px 42px 30px; background-color: #F2FCFE"
                    >
                        <table
                            role="presentation"
                            style="
                    width: 100%;
                    border-collapse: collapse;
                    border: 0;
                    border-spacing: 0;
                  "
                        >
                            <tr>
                                <td
                                    class="dark-text"
                                    style="
                        padding: 0 0 20px 0;
                        color: black;
                        font-family: 'Poppins', sans-serif;
                        font-size: 16px;
                        line-height: 24px;
                      "
                                >
                                    <strong>{{ $greeting ?? 'Hey!' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="dark-text"
                                    style="
                        padding: 0 0 20px 0;
                        color: black;
                        font-family: 'Poppins', sans-serif;
                        font-size: 16px;
                        line-height: 24px;
                      "
                                >
                                    {!! $body ?? '<p>No content provided.</p>' !!}

                                </td>
                            </tr>


                            <tr>
                                <td
                                    style="
                        padding: 10px 0 10px 0;
                        border-top: 1px solid #eeeeee;
                      "
                                ></td>
                            </tr>
                            <tr>
                                <td
                                    class="dark-text"
                                    style="
                        padding: 0 0 10px 0;
                        color: black;
                        font-family: 'Poppins', sans-serif;
                        font-size: 16px;
                        line-height: 24px;
                      "
                                >
                                    <strong>Need Help?</strong>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="dark-subtext"
                                    style="
                        padding: 0 0 20px 0;
                        color: #555555;
                        font-family: 'Poppins', sans-serif;
                        font-size: 16px;
                        line-height: 24px;
                      "
                                >
                                    Should you have any questions, please contact our support
                                    team through any of the below channels and they will be
                                    happy to assist you.
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="dark-text"
                                    style="
                        padding: 0;
                        color: black;
                        font-family: 'Poppins', sans-serif;
                        font-size: 16px;
                        line-height: 24px;
                      "
                                >
                                    Best regards,
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td>
                        <!--[if mso]>
                        <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:602px;">
                            <v:fill type="solid" color="#0d0d0d"/>
                            <v:textbox style="mso-fit-shape-to-text:true" inset="0,0,0,0">
                        <![endif]-->
                        <div
                            style="
                    background-color: #0d0d0d;
                    background-image: linear-gradient(#0d0d0d, #0d0d0d);
                  "
                        >
                            <table
                                role="presentation"
                                style="
                      width: 100%;
                      border-collapse: collapse;
                      border: 0;
                      border-spacing: 0;
                    "
                            >
                                <tr>
                                    <td style="padding: 30px">
                                        <table
                                            role="presentation"
                                            style="
                            width: 100%;
                            border-collapse: collapse;
                            border: 0;
                            border-spacing: 0;
                          "
                                        >
                                            <table
                                                width="100%"
                                                cellpadding="0"
                                                cellspacing="0"
                                                role="presentation"
                                                style="border-collapse: collapse"
                                            >
                                                <tr>
                                                    <td align="left" style="padding: 10px">
                                                        <img
                                                            src="https://cards.tradexcellence.co.uk/backend/public/img/logo.png"
                                                            alt="The BlackDuck Card Logo"
                                                            width="160"
                                                            style="height: auto; display: block"
                                                        />
                                                    </td>

                                                    <td align="right" style="padding: 10px">
                                                        <table
                                                            role="presentation"
                                                            cellpadding="0"
                                                            cellspacing="0"
                                                        >
                                                            <tr>
                                                                <td style="padding: 0 2px">
                                                                    <a
                                                                        href="#"
                                                                        target="_blank"
                                                                        style="
                                          display: inline-block;
                                          width: 30px;
                                          height: 30px;
                                          border-radius: 50%;
                                          background-color: #F2FCFE;
                                          text-align: center;
                                          line-height: 30px;
                                        "
                                                                    >
                                                                        <img
                                                                            src="https://cards.tradexcellence.co.uk/backend/public/img/instagram.png"
                                                                            alt="Instagram"
                                                                            width="18"
                                                                            height="18"
                                                                            style="
                                            vertical-align: middle;
                                            border: none;
                                          "
                                                                        />
                                                                    </a>
                                                                </td>
                                                                <td style="padding: 0 2px">
                                                                    <a
                                                                        href="#"
                                                                        target="_blank"
                                                                        style="
                                          display: inline-block;
                                          width: 30px;
                                          height: 30px;
                                          border-radius: 50%;
                                          background-color: #F2FCFE;
                                          text-align: center;
                                          line-height: 30px;
                                        "
                                                                    >
                                                                        <img
                                                                            src="https://cards.tradexcellence.co.uk/backend/public/img/facebook.png"
                                                                            alt="Facebook"
                                                                            width="18"
                                                                            height="18"
                                                                            style="
                                            vertical-align: middle;
                                            border: none;
                                          "
                                                                        />
                                                                    </a>
                                                                </td>
                                                                <td style="padding: 0 2px">
                                                                    <a
                                                                        href="#"
                                                                        target="_blank"
                                                                        style="
                                          display: inline-block;
                                          width: 30px;
                                          height: 30px;
                                          border-radius: 50%;
                                          background-color: #F2FCFE;
                                          text-align: center;
                                          line-height: 30px;
                                        "
                                                                    >
                                                                        <img
                                                                            src="https://cards.tradexcellence.co.uk/backend/public/img/telegram.png"
                                                                            alt="Telegram"
                                                                            width="18"
                                                                            height="18"
                                                                            style="
                                            vertical-align: middle;
                                            border: none;
                                          "
                                                                        />
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>

                                            <tr>
                                                <td
                                                    align="center"
                                                    style="
                                padding-bottom: 10px;
                                font-family: 'Poppins', sans-serif;
                                font-size: 14px;
                                color: #F2FCFE;
                              "
                                                >
                                                    Send any inquiries to
                                                    <a
                                                        href="mailto:support@tradexcellence.co.uk"
                                                        style="
                                  color: #F2FCFE;
                                  text-decoration: underline;
                                "
                                                    >support@tradexcellence.co.uk</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    align="center"
                                                    style="
                                padding-bottom: 10px;
                                font-family: 'Poppins', sans-serif;
                                font-size: 14px;
                                color: #cccccc;
                              "
                                                >
                                                    All rights reserved. © 2025 The Tradexcellence.
                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    style="
                                border-top: 1px solid rgba(255, 255, 255, 0.404);
                                margin-bottom: 400px;
                              "
                                                ></td>
                                            </tr>

                                            <tr>
                                                <td
                                                    align="center"
                                                    style="
                                font-family: 'Poppins', sans-serif;
                                font-size: 14px;
                                color: #cccccc;
                              "
                                                >
                                                    <a
                                                        href="#"
                                                        style="
                                  color: #cccccc;
                                  text-decoration: underline;
                                "
                                                    >Privacy Policy</a
                                                    >
                                                    |
                                                    <a
                                                        href="#"
                                                        style="
                                  color: #cccccc;
                                  text-decoration: underline;
                                "
                                                    >Terms & Conditions</a
                                                    >
                                                    |
                                                    <a
                                                        href="#"
                                                        style="
                                  color: #cccccc;
                                  text-decoration: underline;
                                "
                                                    >Support</a
                                                    >
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!--[if mso]>
                        </v:textbox>
                        </v:rect>
                        <![endif]-->
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
