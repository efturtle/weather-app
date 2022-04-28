<h2>Get Started </h2>
<br>

<h4>Following these next steps will get the app running</h4>

<ul>
    <li>clone repo</li>
    <li><code>composer install </code></li>
    <li>
        head over to https://openweathermap.org/ and create a user<br>
        With this user we will need to copy the api key (save this for later)
    </li>
    <li>run the following <code> touch database/database.sqlite </code> (which creates the database file)</li>
    <li> <code> cp .env.example .env </code> </li>
    <li>place the previously copied key in the .env file variable called weather key </li>
    <li>
        <code>
            php artisan key generate
        </code>
    </li>
    <li>
        <code>
            php artisan migrate
        </code>
    </li>
    <li>
        <code>
            php artisan serve
        </code>
    </li>
    
<h3> Instructions on how to use the API </h3>
    <p>The current url's that are enabled are the index and store methods  </p>
    <br>
    <p> The url for those are </p>
    <ul>
        <li> GET api/v1/weather </li>
        <li>
            POST api/v1/weather <br>
            Which requires an input field date in a date format 'Y-m-d'
        </li>
    </ul>
