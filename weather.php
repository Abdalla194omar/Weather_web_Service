<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;

$cities = json_decode(file_get_contents('egypt-cities.json'), true);

$weather = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cityId = $_POST['city_id'] ?? null;
    $method = $_POST['method'] ?? null;
    $apiKey = '3b1ee36841650b6c9d2bd04eebeff8b2';

    if (!$cityId || !$method) {
        $error = "Please select a city and a method.";
    } else {
        $url = "http://api.openweathermap.org/data/2.5/weather?id={$cityId}&appid={$apiKey}&units=metric";

        try {
            if ($method === 'guzzle') {
                $client = new Client();
                $response = $client->get($url);
                $weather = json_decode($response->getBody(), true);
            } elseif ($method === 'curl') {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new Exception(curl_error($ch));
                }
                curl_close($ch);
                $weather = json_decode($result, true);
            } else {
                $error = "Invalid method selected.";
            }
        } catch (Exception $e) {
            $error = "Error fetching weather data: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Weather Forecast</title>
</head>

<body>
    <h1>Weather Forecast for Egypt</h1>
    <form method="POST" action="">
        <label><strong>Select a city:</strong></label><br>
        <select name="city_id" required>
            <option value="">-- Choose a city --</option>
            <?php foreach ($cities as $city): ?>
            <option value="<?= htmlspecialchars($city['id']) ?>"
                <?= (isset($_POST['city_id']) && $_POST['city_id'] == $city['id']) ? 'selected' : '' ?>>
                EG >> <?= htmlspecialchars($city['name']) ?>
            </option>
            <?php endforeach; ?>
        </select><br><br>

        <label><strong>Choose method:</strong></label><br>
        <label><input type="radio" name="method" value="guzzle"
                <?= (isset($_POST['method']) && $_POST['method'] == 'guzzle') ? 'checked' : '' ?>> Guzzle</label><br>
        <label><input type="radio" name="method" value="curl"
                <?= (isset($_POST['method']) && $_POST['method'] == 'curl') ? 'checked' : '' ?>> cURL</label><br><br>

        <input type="submit" value="Get Weather">
    </form>

    <?php if ($error): ?>
    <p style="color:red;"><strong><?= htmlspecialchars($error) ?></strong></p>
    <?php endif; ?>

    <?php if ($weather): ?>
    <h2>Weather in <?= htmlspecialchars($weather['name']) ?></h2>
    <p><?= date("l g:i a") ?><br><?= date("jS F, Y") ?></p>
    <p><?= ucfirst(htmlspecialchars($weather['weather'][0]['description'])) ?></p>
    <img src="http://openweathermap.org/img/wn/<?= htmlspecialchars($weather['weather'][0]['icon']) ?>@2x.png"
        alt="weather icon" />
    <p>Temperature: <?= htmlspecialchars($weather['main']['temp_min']) ?>°C to
        <?= htmlspecialchars($weather['main']['temp_max']) ?>°C</p>
    <p>Humidity: <?= htmlspecialchars($weather['main']['humidity']) ?>%</p>
    <p>Wind Speed: <?= htmlspecialchars($weather['wind']['speed']) ?> km/h</p>
    <?php endif; ?>
</body>

</html>