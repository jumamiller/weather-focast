<?php
/**
 * @package weather
 */
/*
Plugin Name: Weather API
Plugin URI: https://weather.com/
Description: Used by millions, Weather API is quite possibly the best way in the world to <strong>protect view the weather details from planet earth</strong>.
you only need to set up your API key.
Version: 1.0
Author: Masterclass Solutions Limited
Author URI: https://masterclass.co.ke/
License: GPLv2 or later
Text Domain: Masterclass
*/
//header and footer include
class Weather {
    public function __construct() {
        // Hook into the admin menu
        add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
        add_action( 'admin_init', array( $this, 'setup_sections' ) );
        add_action( 'admin_init', array( $this, 'setup_fields' ) );
    }
    public function create_plugin_settings_page() {
        // Add the menu item and page
        $page_title = 'Weather Prediction Settings';
        $menu_title = 'Weather App';
        $capability = 'manage_options';
        $slug = 'weather_application';
        $callback = array( $this, 'plugin_settings_page_content' );
        $icon = 'dashicons-admin-plugins';
        $position = 100;

        add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
    }

    /**
     * @return void
     */
    public function plugin_settings_page_content() {
        if( $_POST['updated'] === 'true' ){
            $this->handle_form();
        } ?>
        <div class="wrap">
            <h2>Settings Page</h2>
            <form method="POST">
                <input type="hidden" name="updated" value="true" />
                <?php wp_nonce_field( 'weather_api_form_update', 'weather_api_form' ); ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th><label for="api_key">API KEY</label></th>
                        <td><input name="api_key" id="api_key" type="text" value="<?php echo get_option('weather_api_key'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="city_id">City ID</label></th>
                        <td><input name="city_id" id="city_id" type="text" value="<?php echo get_option('weather_city_id'); ?>" class="regular-text" /></td>
                    </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes!">
                </p>
            </form>
        </div> <?php
    }

    /**
     * @return void
     */
    public function setup_sections() {
        add_settings_section( 'our_first_section', 'Weather Application', false, 'weather_application' );
    }

    /**
     * @return void
     */
    public function setup_fields() {
        add_settings_field( 'our_first_field', 'API KEY', array( $this, 'field_callback' ), 'weather_application', 'our_first_section' );
    }

    /**
     * @param $arguments
     * @return void
     */
    public function field_callback( $arguments ) {
        echo '<input name="our_first_field" id="our_first_field" type="text" value="' . get_option( 'our_first_field' ) . '" />';
    }

    /**
     * @return void
     */
    public function handle_form() {
        if(
            ! isset( $_POST['weather_api_form'] ) ||
            ! wp_verify_nonce( $_POST['weather_api_form'], 'weather_api_form_update' )
        ){ ?>
            <div class="error">
                <p>Sorry, your nonce was not correct. Please try again.</p>
            </div> <?php
            exit;
        } else {
            $api_key = sanitize_text_field( $_POST['api_key'] );
            $city_id = sanitize_text_field( $_POST['city_id'] );

            if( !empty( $api_key) && !empty($city_id)){
                update_option( 'weather_api_key', $api_key );
                update_option( 'weather_city_id', $city_id );
                ?>
                <div class="updated">
                    <p>Your fields were saved!</p>
                </div> <?php
            } else { ?>
                <div class="error">
                    <p>Your API Key /city ID is invalid.</p>
                </div> <?php
            }
        }
    }
}
new Weather();
//shortcode
add_shortcode('weather_sc','display_init');
function display_init(): string
{
    $apiKey = get_option('weather_api_key');
    $cityId = get_option('weather_city_id');
    $googleApiUrl = "http://api.openweathermap.org/data/2.5/forecast?id=$cityId&appid=" . $apiKey;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    curl_close($ch);
    $data = json_decode($response);
    $currentTime = date('l g:i a', time());
    //city info
    $status='';
    $cityDetails=$data->city;
    $cityName=$cityDetails->name;
    $sunrise= date("d F Y H:i:s", $cityDetails->sunrise);
    $sunset= date("d F Y H:i:s", $cityDetails->sunset);
    $lat= $cityDetails->coord->lat;
    $long= $cityDetails->coord->lon;
    foreach ($data->list as $info) {
        $icon = $info->weather[0]->icon;
        $weather_description = ucwords($info->weather[0]->description);
        $humidity = $info->main->humidity;
        $wind = $info->wind->speed;
        $temp_min = $info->main->temp_min;
        $temp_max = $info->main->temp_max;
        $pressure = $info->main->pressure;
        $dt = $info->dt_txt;

        $status.= "
        <div class='report-container'>
            <h2> Weather Status In $cityName (Lat $lat, Long: $long) (Predictions) On $dt (sunrise: $sunrise sunset: $sunset )</h2>
            <div class='time'>
                <div>$currentTime</div>
                <div><strong>$weather_description</strong></div>
            </div>
            <div class='weather-forecast'>
                <img
                    src='https://openweathermap.org/img/w/$icon.png' class='weather-icon' 
                    alt=''/> $temp_max °C 
                    <span class='min-temperature'>$temp_min °C</span>
            </div>
            <div class='time'>
                <div>Humidity: $humidity %</div>
                <div>Wind: $wind km/h</div>
                <div>Pressure: $pressure pa</div>
            </div>
        </div>
        
    ";
    }
    return $status;
}
//include("./includes/shared/footer.php");
