<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Farmacia La Esperanza</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            body {
                background-color: #E8F9EE;
                margin: 0;
                padding: 0;
                font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            
            .farmacia-title {
                font-size: 3rem;
                font-weight: 700;
                color: #2D5A3D;
                text-align: center;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
                padding: 20px;
            }
            
            @media (max-width: 768px) {
                .farmacia-title {
                    font-size: 2rem;
                }
            }
        </style>
    </head>
    <body>
        <h1 class="farmacia-title">FARMACIA LA ESPERANZA</h1>
    </body>
</html>