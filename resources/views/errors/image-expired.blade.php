<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Weekend Offer Over | Pothys</title>

    <style>

        /* ==============================
           RESET
        ============================== */

        * {
            box-sizing: border-box;
        }


        /* ==============================
           BODY
        ============================== */

        body {
            margin: 0;
            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f5f5;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 20px;
        }


        /* ==============================
           MAIN PAGE
        ============================== */

        .page {
            width: 100%;
            max-width: 650px;

            background: #ffffff;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 8px 35px rgba(0, 0, 0, 0.10);
        }


        /* ==============================
           HEADER
        ============================== */

        .header {
            background: #ffffff;

            padding: 25px 20px 18px;

            text-align: center;

            border-bottom: 1px solid #eeeeee;
        }


        /* ==============================
           LOGO
        ============================== */

        .logo {
            max-width: 180px;
            max-height: 70px;

            object-fit: contain;
        }


        /* ==============================
           BRAND
        ============================== */

        .brand {
            margin-top: 8px;

            font-size: 22px;

            font-weight: 700;

            letter-spacing: 2px;

            color: #006b3c;
        }


        /* ==============================
           OFFER CONTENT
        ============================== */

        .content {
            padding: 45px 30px;

            text-align: center;
        }


        /* ==============================
           CLOCK ICON
        ============================== */

        .icon {
            width: 75px;
            height: 75px;

            margin: 0 auto 22px;

            border-radius: 50%;

            background: #fff8e1;

            border: 1px solid #f1d58a;

            color: #c59d2a;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 36px;
        }


        /* ==============================
           TITLE
        ============================== */

        h1 {
            margin: 0 0 12px;

            font-size: 26px;

            color: #333333;
        }


        /* ==============================
           MESSAGE
        ============================== */

        .message {
            margin: 0 auto;

            max-width: 470px;

            font-size: 16px;

            line-height: 1.7;

            color: #777777;
        }


        /* ==============================
           STATUS
        ============================== */

        .status {
            display: inline-block;

            margin-top: 22px;

            padding: 8px 20px;

            border-radius: 30px;

            background: #fff8e1;

            border: 1px solid #f1d58a;

            color: #9a7614;

            font-size: 14px;

            font-weight: 600;
        }


        /* ==============================
           FOOTER
        ============================== */

        .footer {
            padding: 16px 20px;

            text-align: center;

            background: #ffffff;

            border-top: 1px solid #eeeeee;

            color: #999999;

            font-size: 12px;
        }


        /* ==============================
           MOBILE RESPONSIVE
        ============================== */

        @media (max-width: 480px) {

            body {
                padding: 15px;
            }

            .content {
                padding: 35px 20px;
            }

            .header {
                padding: 22px 15px 16px;
            }

            .logo {
                max-width: 150px;
                max-height: 60px;
            }

            .brand {
                font-size: 20px;
            }

            .icon {
                width: 65px;
                height: 65px;

                font-size: 30px;
            }

            h1 {
                font-size: 23px;
            }

            .message {
                font-size: 14px;
            }

            .status {
                font-size: 13px;
            }

        }

    </style>

</head>


<body>


    <!-- ==============================
         MAIN PAGE
    ============================== -->

    <div class="page">


        <!-- ==============================
             HEADER
        ============================== -->

        <div class="header">

            <img
                src="{{ asset('assets/images/pothys-logo-white-text.png') }}"
                alt="Pothys"
                class="logo"
                onerror="this.style.display='none'"
            >

            <!-- <div class="brand">
                POTHYS
            </div> -->

        </div>


        <!-- ==============================
             OFFER CONTENT
        ============================== -->

        <div class="content">


            <!-- CLOCK -->

            <div class="icon">
                ⏰
            </div>


            <!-- TITLE -->

            <h1>
                Weekend Offer Over
            </h1>


            <!-- MESSAGE -->

            <p class="message">
                This weekend offer has ended.
            </p>


            <!-- STATUS -->

            <div class="status">
                Offer Closed
            </div>


            <!-- ADDITIONAL MESSAGE -->

            <p
                class="message"
                style="margin-top: 20px;"
            >
                Please check Pothys for the latest offers
                and deals.
            </p>


        </div>


        <!-- ==============================
             FOOTER
        ============================== -->

        <div class="footer">

            © {{ date('Y') }} Pothys.
            All Rights Reserved.

        </div>


    </div>


</body>

</html>