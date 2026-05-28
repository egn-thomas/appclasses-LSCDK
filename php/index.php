<?php
$apiUrl = getenv('API_URL') ?: 'http://localhost:3000';
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppClasses LSCDK</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
        }

        button {
            padding: 0.6rem 1rem;
            font-size: 1rem;
        }

        pre {
            background: #f4f4f4;
            padding: 1rem;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <h1>AppClasses LSCDK</h1>
    <p>Front PHP connecté à l'API Node.js.</p>
    <button id="ping">Tester l'API</button>
    <div id="output"></div>
    <p>Deploy automatique : OK</p>

    <script>
        const apiUrl = '<?php echo htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8'); ?>';
        const output = document.getElementById('output');
        document.getElementById('ping').addEventListener('click', async () => {
            output.textContent = 'Connexion en cours...';
            try {
                const response = await fetch(`${apiUrl}/api/health`);
                const result = await response.json();
                output.innerHTML = `<pre>${JSON.stringify(result, null, 2)}</pre>`;
            } catch (error) {
                output.innerHTML = `<pre>Erreur : ${error.message}</pre>`;
            }
        });
    </script>
</body>

</html>