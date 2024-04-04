<?php
/**
 * Plugin Name: Security Api Caller
 * Description: make bots securere.
 * Version: 1.0
 * Author: HH
 * Text Domain: job-screenshot-uploader
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

//  avoid function redeclaration errors
if (!function_exists('SAC_my_plugin_function')) {
    function sac_my_plugin_function()
    {

    }
}

if (!defined('SAC_PLUGIN_VERSION')) {
    define('SAC_PLUGIN_VERSION', '1.0.0');
}

if (!defined('SAC_PLUGIN_DIR')) {
    define('SAC_PLUGIN_DIR', plugin_dir_url(__FILE__));
}

function sac_plugin_scripts()
{
    wp_enqueue_style('sac-css', SAC_PLUGIN_DIR . 'assets/css/style.css');
    wp_enqueue_script('sac-js', SAC_PLUGIN_DIR . 'assets/js/main.js', array(), true);



    //$key = 'Bearer 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';
    //$temp = 0.5;
    //$model = 'gpt-4-0125-preview';
    // Localize the script with correct handle


    wp_localize_script(
        'sac-js',
        'sac_ajax_object',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            //'prompts' => $prompts,
            //'key_auth' => $key,
            //'temp' => $temp,
            //'model' => $model
        )
    );
}
add_action('wp_enqueue_scripts', 'sac_plugin_scripts');


// Register activation hook.
register_activation_hook(__FILE__, 'sac_create_db_table');
//table for saving cv data
function sac_create_db_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_cv';

    // Charset to use in the database table.
    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create your table.
    // Using LONGTEXT for CV column to accommodate large text.
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        UserID bigint(20) NOT NULL,
        CV LONGTEXT NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


// Register AJAX actions for logged-in users
add_action('wp_ajax_sac_save_cv', 'sac_handle_save_cv');

function sac_handle_save_cv()
{
    // Check for the nonce for security
    //check_ajax_referer('sac_secure_cv_nonce', 'security');

    global $wpdb;
    $user_id = get_current_user_id();
    $cv_text = isset ($_POST['cvText']) ? wp_strip_all_tags($_POST['cvText']) : '';

    // Table name
    $table_name = $wpdb->prefix . 'user_cv';

    // Fetch all CVs for this user
    $existing_cvs = $wpdb->get_results($wpdb->prepare("SELECT CV FROM $table_name WHERE UserID = %d", $user_id), ARRAY_A);

    // Loop through all existing CVs and check if any match the new CV
    foreach ($existing_cvs as $existing_cv) {
        if ($existing_cv['CV'] === $cv_text) {
            wp_send_json_error(array('message' => 'This CV has already been uploaded.'));
            wp_die();
        }
    }

    // Data to insert
    $data = array(
        'UserID' => $user_id,
        'CV' => $cv_text
    );

    // Proceed with inserting the new CV as no duplicates were found
    $inserted = $wpdb->insert($table_name, $data);

    if ($inserted) {
        // Return success response
        wp_send_json_success(array('message' => 'CV saved successfully.'));
    } else {
        // Handle error during insert
        wp_send_json_error(array('message' => 'Failed to save CV.'));
    }

    // Don't forget to stop execution afterward
    wp_die();
}

function sac_handle_update_cv()
{
    // Check for the nonce for security
    //check_ajax_referer('sac_secure_cv_nonce', 'security');

    global $wpdb;
    $user_id = get_current_user_id();
    $cv_text = isset ($_POST['cvText']) ? wp_strip_all_tags($_POST['cvText']) : '';

    // Table name
    $table_name = $wpdb->prefix . 'user_cv';

    // Attempt to fetch the existing CV for this user
    $existing_cv = $wpdb->get_row($wpdb->prepare("SELECT CV FROM $table_name WHERE UserID = %d", $user_id), ARRAY_A);

    // Data for insert/update
    $data = array(
        'UserID' => $user_id,
        'CV' => $cv_text
    );

    // If a CV already exists for the user, update it
    if ($existing_cv) {
        $where = array('UserID' => $user_id);
        $updated = $wpdb->update($table_name, $data, $where);

        if ($updated !== false) { // Check for boolean false to confirm the update was successful
            wp_send_json_success(array('message' => 'CV updated successfully.'));
        } else {
            // Handle error during update
            wp_send_json_error(array('message' => 'Failed to update CV.'));
        }
    } else {
        // If no existing CV, proceed with inserting the new CV
        $inserted = $wpdb->insert($table_name, $data);

        if ($inserted) {
            wp_send_json_success(array('message' => 'CV saved successfully.'));
        } else {
            // Handle error during insert
            wp_send_json_error(array('message' => 'Failed to save CV.'));
        }
    }

    wp_die(); // Stop execution
}

add_action('wp_ajax_sac_handle_update_cv', 'sac_handle_update_cv');

// Register AJAX action for logged-in users to retrieve their CVs
add_action('wp_ajax_sac_handle_get_user_cvs', 'sac_handle_get_user_cvs');




function sac_handle_get_user_cvs()
{
    // Verify the security nonce
    // check_ajax_referer('sac_secure_cv_nonce', 'security');

    global $wpdb;
    $user_id = get_current_user_id();

    // Ensure we have a valid, logged-in user
    if ($user_id <= 0) {
        wp_send_json_error(array('message' => 'User is not logged in.'));
        wp_die();
    }

    // Define the table name
    $table_name = $wpdb->prefix . 'user_cv';

    // Query to retrieve the CVs
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT CV FROM $table_name WHERE UserID = %d",
            $user_id
        ),
        ARRAY_A
    );

    if (!empty ($results)) {
        // If results exist, send them back
        wp_send_json_success(array('cvs' => $results));
    } else {
        // If no results, send an appropriate message
        wp_send_json_error(array('message' => 'No CVs found.'));
    }

    // Stop execution to return the result
    wp_die();
}

function sac_handle_get_single_user_cv()
{
    // Verify the security nonce
    // check_ajax_referer('sac_secure_cv_nonce', 'security');

    global $wpdb;
    $user_id = get_current_user_id();

    // Ensure we have a valid, logged-in user
    if ($user_id <= 0) {
        wp_send_json_error(array('message' => 'User is not logged in.'));
        wp_die();
    }

    // Define the table name
    $table_name = $wpdb->prefix . 'user_cv';

    // Query to retrieve a single CV for the user
    $result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT CV FROM $table_name WHERE UserID = %d LIMIT 1",
            $user_id
        ),
        ARRAY_A
    );

    if (!empty ($result)) {
        // If result exists, send it back
        wp_send_json_success(array('cv' => $result));
    } else {
        // If no result, send an appropriate message
        wp_send_json_error(array('message' => 'No CV found.'));
    }

    // Stop execution to return the result
    wp_die();
}

add_action('wp_ajax_sac_handle_get_single_user_cv', 'sac_handle_get_single_user_cv');
// Use this hook for non-authenticated users if needed
// add_action('wp_ajax_nopriv_sac_handle_get_single_user_cv', 'sac_handle_get_single_user_cv');

//---------------------------------------------------------------- feedback on application and cv full --------------------------------------------------------------------------------------------------------------------------------

function threeStagePrompt()
{
    require ("html/application_cv_full_html.php");
    load_three_stage_prompt();
    return $html;
}



add_shortcode('application_cv_feedback_full', 'threeStagePrompt');

function load_three_stage_prompt()
{
    wp_enqueue_style('three-stage-prompt-css', plugin_dir_url(__FILE__) . '/assets/css/three-stage-prompt.css');
    wp_enqueue_script('three-stage-prompt-js', plugin_dir_url(__FILE__) . '/assets/js/three-stage-prompt.js', array('jquery'), null, true);
}

add_action('wp_ajax_call_chat_gpt', 'call_chat_gpt_api');
add_action('wp_ajax_nopriv_call_chat_gpt', 'call_chat_gpt_api'); // for users not logged in

function call_chat_gpt_api()
{
    // Check for WP Nonce for security (if you've passed one from your JS call)
    require ("prompts/application_cv_full_html.php");
    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];

    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}









add_action('wp_ajax_call_chat_gpt_bot', 'call_chat_gpt_api_bot');
add_action('wp_ajax_nopriv_call_chat_gpt_bot', 'call_chat_gpt_api_bot'); // for users not logged in

function call_chat_gpt_api_bot()
{
    $prompts = ['Baseret på mit CV, ansøgning og det jobopslag jeg er interesseret i, bedes du skrive to afsnit. I det første afsnit skal du vurdere, hvor godt jeg matcher stillingen, og i det andet afsnit skal du identificere og fremhæve tre kompetencer, der er relevante for jobopslaget.



Struktur for det første afsnit:

Overskrift: Min vurdering af matchet til stillingen
Giv en vurdering af, hvor godt jeg matcher stillingen ved at anvende betegnelserne "Godt match", "Moderat match" eller "Lavt match". Vær realistisk i din vurdering, og tag højde for, om jobopslaget kræver en specifik uddannelse. Du skal forsøge at være positiv, hvis det er et lavt match.

Begrund herefter din vurdering af, hvordan jeg matcher stillingen.

[Lav mellemrum]

Overskrift: Fremhæv disse kompetencer
Vælg tre kompetencer fra mit CV og ansøgning, som jeg bør fremhæve i relation til jobopslaget og den profil, de søger. Oplist de 3 kompetencer i punktform, så det er let at læse. Forklar kort, hvorfor de er vigtige at nævne.

Den samlede analyse skal være præcis og kort, maksimalt 600 tegn, og uden brug af formattering. Du skal skrive direkte til mig uden at bruge mit navn. Det er meget vigtigt at sproget er direkte, letforståeligt og jordnært, så det lyder naturligt for en dansktalende person. Undgå generisk tekst. Du skal derfor skrive i et naturligt og direkte sprog.

Din samlede besked skal være 700 tegn eller kortere.',

        "Du skal skrive en personlig og målrettet indledning til mit CV, der matcher præcist med det job jeg søger. Indledningen skal både afspejle mine personlige og faglige kvaliteter, som matcher med jobopslaget og den ansøgning, jeg har skrevet. 
    
Sørg for at anvende en stil og tone, der føles oprigtig og jordnær, og som er naturligt for en dansktalende person. Teksten skal være unik og specifikt rettet den stilling, jeg søger. Undgå generiske floskler og sørg for, at sproget er direkte og letforståeligt.

Struktur:

1. Begynd dette afsnit direkte uden overskrift


Længde: Maksimum 300 characters eller mindre.
Indhold: Beskriv overordnet min faglige profil. Teksten skal indeholde konkrete eksempler på mine færdigheder og hvordan de har bidraget til mine tidligere roller. Fokuser på de mest relevante kvalifikationer i forhold til jobopslaget. Formålet med afsnittet er at overordnet matche min faglige profil og evner i forhold til jobopslaget. 

2. Overskrift: Derfor skal I vælge mig
[Lav mellemrum]

Længde: Maksimum 400 characters eller mindre.
Indhold: Kortlæg præcist, hvordan mine nøglekompetencer og personlige egenskaber matcher stillingsopslaget. Pas på det ikke bliver en gentagelse af mit CV, du skal tale om hvordan jeg konkret vil løfte opgaverne i jobopslaget på baggrund af min erfaring. Du skal være personlig og konkret, og fortælle hvad jeg har tænkt at gøre fremadrettet i stillingen. Husk at komme med konkrete eksempler.

Eksempeltekst til reference:
'Min motivation strækker sig ud over det rent faglige. Jeg brænder for at gøre en forskel i folks liv omkring mig og har en naturlig evne til at bygge relationer takket være min stærke empati. Dette kan blandt andet ses gennem mit arbejde som frivillig, som har været med til at styrke min evne til at møde borgeren i øjenhøjde. At arbejde selvstændigt er en af mine spidskompetencer, og jeg formår at prioritere og håndtere mine opgaver med omtanke.'

Det er vigtigt at du ikke gentager dig selv i de 2 afsnit. Det første afsnit beskriver min falige profil overordnet, hvor det næste afsnit beskriver specifict hvordan jeg vil løfte opgaverne i jobopslaget. Din samlede besked skal være 700 characters eller kortere. Skriv uden formattering inklusiv fed skrift i dit output og overskrifterne."
        ,

        'Din opgaven er at identificere og anbefale specifikke nøgleord som matcher med jobopslaget, som jeg kan indsætte i mit CV. Nøgleordene skal udvælges på baggrund af jobopslaget, mit CV og min ansøgning. Du skal kun udvælge nøgleord som jeg besidder. Du må ikke opdigte nøgleord jeg skal inkludere i mit CV, hvis ikke jeg besidder disse.


    Struktur:

1. Overskrift: Brug disse nøgleord
[Lav mellemrum]

    -"Nøgleord 1"
    -"Nøgleord 2"


Skriv 10 nøgleord i alt. Vælg nøgleord, der direkte relaterer til de vigtigste krav og ønsker nævnt i jobopslaget. Disse kan inkludere:

Specifikke færdigheder (f.eks., "projektledelse", "kundeservice").
Teknologiske kompetencer (f.eks., "Excel", "Salesforce").
Branchespecifik jargon eller terminologi.
Personlige kvaliteter og kompetencer (f.eks., "teamspiller", "proaktiv").

2. Overskrift: Her skal du indsætte nøgleordende
[Lav mellemrum]

Du skal fortælle mig meget specifikt hvor jeg skal indsætte hvert nyt nøgleord i mit CV. Det vil være svært for mig ellers at finde ud af hvor jeg skal indsætte de nøgleord du har fundet frem til. Nøgleordene skal kun indsættes i mit CV. Skriv uden formattering inklusiv fed skrift i dit output.

Du skal bruge mellemrum mellem hvert nøgleord i punktlisten, så det holdes overskueligt. Skriv uden formattering.

Det er herudover vigtigt at din analyse maksimalt fylder 800 characters! Skriv i et sprog som er direkte og naturligt at forstå for en dansktalende person.'
        ,

        "Din opgave er at analysere og forbedre min jobansøgning for at sikre, at den er engagerende, relevant og matcher jobopslaget. Din analyse skal munde ud i konkrete, direkte anvendelige forbedringsforslag. Begrund dine forslag til forbedringer. Analysen skal være kort og må maksimalt fylde 1000 characters. Skriv uden formattering inklusiv fed skrift i dit output.


    Struktur:

    1. Overskrift: Forbedringforslag nummer 1


    Du skal udvælge første nøgleområder, hvor jeg skal forbedre ansøgningen. Du skal komme med konkrete eksempler på hvordan jeg kan omskrive teksten og en begrundelse for hvorfor jeg skal ændre det. Dine eksempler skal være meget konkrete, og skal kunne indsættes direkte i mmin ansøgning. Skriv uden formattering inklusiv fed skrift i dit output.

  2. Overskrift: Forbedringforslag nummer 2


    Du skal udvælge andet nøgleområder,  komme med konkrete eksempler på hvordan jeg kan omskrive teksten og en begrundelse for hvorfor jeg skal ændre det. Dine eksempler skal være meget konkrete, og skal kunne indsættes direkte i mmin ansøgning. Skriv uden formattering inklusiv fed skrift i dit output.

Skriv i et sprog som er direkte og naturligt at forstå for en dansktalende person. Fordi du kun må skrive 1000 characters, så det er vigtigt du kun udvælger det vigtigste jeg skal ændre i min ansøgning. Dit output skal være uden formattering.

Eksempeltekst til reference:
'Min motivation strækker sig ud over det rent faglige. Jeg brænder for at gøre en forskel i folks liv omkring mig og har en naturlig evne til at bygge relationer takket være min stærke empati. Dette kan blandt andet ses gennem mit arbejde som frivillig, som har været med til at styrke min evne til at møde borgeren i øjenhøjde. At arbejde selvstændigt er en af mine spidskompetencer, og jeg formår at prioritere og håndtere mine opgaver med omtanke.

Din samlede besked skal være 1000 tegn eller kortere."
        ,

        "Din opgave er at lave en specifik udviklingsplan, så jeg kan udvide mine kompetencer, og matche med jobopslaget bedre i fremtiden. Planen skal være specifik og handlingsorienteret. Du skal gennemgå mit CV, jobansøgning og stillingsopslaget for at se, hvor jeg mangler kompetencer.

    Denne plan skal detaljeret adressere hvilke specifikke færdigheder, erfaringer eller kurser jeg skal fokusere på for at optimere min kvalifikation til lignende stillinger i fremtiden.

Struktur:

1. Overskrift: Sådan forbedre du dine faglige kompetencer
Identificer de vigtigste faglige kompetencer jeg mangler i forhold til stillingsopslaget. Kom med konkrete forslag til hvordan jeg kan forbedre disse. Du skal være så konkret som muligt, så jeg nemt kan handle på baggrund af dine forslag.


2. Overskrift: Personlige kompetencer
Identificer de vigtigste personlige kompetencer jeg mangler i forhold til stillingsopslaget. Kom med konkrete forslag til hvordan jeg kan forbedre disse. Du skal være så konkret som muligt, så jeg nemt kan handle på baggrund af dine forslag.


Husk at anvende mellemrum så teksten bliver overskuelig at læse. Skriv uden formattering inklusiv fed skrift i dit output og overskrifterne. Det er herudover vigtigt at din analyse maksimalt fylder 800 characters! Skriv i et sprog som er direkte og naturligt at forstå for en dansktalende person."

        ,


    ];

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];

    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response


}

function bot()
{
    global $bot_flag;
    $bot_flag = true;
    $html = '
   <head>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <script
      src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"
      integrity="sha512-qZvrmS2ekKPF2mSznTQsxqPgnpkI4DNTlrdUmTzrDgektczlKNRRhy5X5AAOnx5S09ydFYWWNSfcEqDTTHgtNA=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    ></script>
   
   
   </head>
  
  <div class="page-container insert-container">
      <div class="insert-inner-container">
        <div class="insert-common-container cv-container">
          <h2>Indsæt dit CV</h2>
          <textarea
            name="insert-cv"
            id="insert-cv"
            placeholder="Indsæt dit CV her..."
          ></textarea>
          <div class="or-divider">
            <hr class="divider-line" />
            <span class="or-text">Eller</span>
            <hr class="divider-line" />
          </div>
          <label class="label-head">Upload dit CV (kun i PDF-format)</label>
          <div class="custom">
            <input
              type="file"
              class="form-control valid"
              type="text"
              name="pdf-input-cv"
              id="pdf-input-cv"
              accept="application/pdf"
              required
            />
          </div>
        </div>
        <div class="insert-common-container job-container">
          <h2>Indsæt jobopslaget</h2>
          <textarea
            name="insert-jobDescription"
            id="insert-jobDescription"
            placeholder="Indsæt jobopslaget her..."
          ></textarea>
          <div class="or-divider">
            <hr class="divider-line" />
            <span class="or-text">Eller</span>
            <hr class="divider-line" />
          </div>
          <label class="label-head"
            >Upload jobopslaget (kun i PDF-format)</label
          >
          <div class="custom">
            <input
              type="file"
              class="form-control valid"
              type="text"
              name="pdf-input-job"
              id="pdf-input-job"
              accept="application/pdf"
              required
            />
          </div>
        </div>
        <div
          class="insert-addtional-container insert-common-container job-container"
        >
          <h2>Indsæt ansøgning</h2>
          <textarea
            name="insert-addtional"
            id="insert-additonal"
            placeholder="Indsæt din ansøgning her..."
          ></textarea>
          <div class="or-divider">
            <hr class="divider-line" />
            <span class="or-text">Eller</span>
            <hr class="divider-line" />
          </div>
          <label class="label-head"
            >Upload ansøgningen (kun i PDF-format)</label
          >
          <div class="custom">
            <input
              type="file"
              class="form-control valid"
              type="text"
              name="pdf-add-feild"
              id="pdf-add-feild"
              accept="application/pdf"
              required
            />
          </div>
        </div>
      </div>
      <button
        style="
          background-color: #008080;
          border-style: solid;
          border-width: 1px 1px 1px 1px;
          border-color: #008080;
          box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.37);
          padding: 15px 20px 15px 20px;
          margin-bottom: 5%;
          border-radius: 15px;
        "
        onclick="clickStart()"
        type="button"
      >
        Start analysen
      </button>
    </div>
    <div class="page-container">
      <div class="page-wrapper">
        <div class="edit-container">
          <h2>Overordnet analyse</h2>
          <div class="edit-btns">
            <button id="btn1" class="start-btn" type="button">
              Skriv afsnit
            </button>
          </div>
          <div class="edit-textArea">
            <div id="loading0" class="loading-screen" style="display: none">
              <div class="spinner"></div>
            </div>
            <label class="editor-header" for="intro-data">
              <span></span>
              <span class="edit-icons">
                <span class="speaker-icon">
                  <?xml version="1.0" encoding="utf-8"?>

                  <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                  <svg
                    version="1.0"
                    id="Layer_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="15px"
                    height="15px"
                    viewBox="0 0 64 64"
                    enable-background="new 0 0 64 64"
                    xml:space="preserve"
                  >
                    <g>
                      <path
                        fill="#ffff"
                        d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z"
                      />
                      <path
                        fill="#ffff"
                        d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z"
                      />
                      <path
                        fill="#ffff"
                        d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z"
                      />
                      <path
                        fill="#ffff"
                        d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z"
                      />
                      <path
                        fill="#ffff"
                        d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z"
                      />
                      <path
                        fill="#ffff"
                        d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z"
                      />
                    </g>
                  </svg>
                </span>
                <span class="copy-icon">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="15"
                    height="15"
                    viewBox="0 0 448 512"
                  >
                    <path
                      fill="white"
                      d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z"
                    />
                  </svg>
                </span>
              </span>
            </label>
            <textarea readonly name="intro-data" class="intro-data"></textarea>
          </div>
          <div class="remake-btn-div">
            <button onclick="clickButton("btn1")" class="other-btn">
              <a
                href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                style="text-decoration: none; color: white"
                >Omskriv teksten</a
              >
            </button>
          </div>
        </div>
        <div class="image-container">
          <img
            style="margin-left: 0px;"
            src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/Screenshot-2024-02-04-084937-1.png"
            alt=""
          />
        </div>
      </div>
    </div>
    <div class="heading-divider">
      <div>
        <hr />
      </div>
      <h2>CV</h2>
      <div>
        <hr />
      </div>
    </div>

    <div class="page-container">
      <div class="page-wrapper">
        <div class="edit-container">
          <h2>CV-tilpasning: Skræddersyet indledning</h2>
          <div class="edit-btns">
            <button id="btn2" class="start-btn" type="button">
              Skriv afsnit
            </button>
          </div>
          <div class="edit-textArea">
            <div id="loading1" class="loading-screen" style="display: none">
              <div class="spinner"></div>
            </div>
            <label class="editor-header" for="intro-data">
              <span></span>
              <span class="edit-icons">
                <span class="speaker-icon">
                  <?xml version="1.0" encoding="utf-8"?>

                  <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                  <svg
                    version="1.0"
                    id="Layer_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="15px"
                    height="15px"
                    viewBox="0 0 64 64"
                    enable-background="new 0 0 64 64"
                    xml:space="preserve"
                  >
                    <g>
                      <path
                        fill="#ffff"
                        d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z"
                      />
                      <path
                        fill="#ffff"
                        d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z"
                      />
                      <path
                        fill="#ffff"
                        d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z"
                      />
                      <path
                        fill="#ffff"
                        d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z"
                      />
                      <path
                        fill="#ffff"
                        d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z"
                      />
                      <path
                        fill="#ffff"
                        d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z"
                      />
                    </g>
                  </svg>
                </span>
                <span class="copy-icon">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="15"
                    height="15"
                    viewBox="0 0 448 512"
                  >
                    <path
                      fill="white"
                      d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z"
                    />
                  </svg>
                </span>
              </span>
            </label>
            <textarea readonly name="intro-data" class="intro-data"></textarea>
          </div>
          <div class="remake-btn-div">
            <button onclick="clickButton(`btn2`)" class="other-btn">
              <a
                href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                style="text-decoration: none; color: white"
                >Omskriv teksten</a
              >
            </button>
          </div>
        </div>
        <div class="image-container">
          <img
            src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/342.png"
            alt=""
          />
        </div>
      </div>
    </div>
    <div class="page-container">
      <div class="page-wrapper">
        <div class="edit-container">
          <h2>CV-tilpasning: Indsæt vigtige nøgleord</h2>
          <div class="edit-btns">
            <button id="btn3" class="start-btn" type="button">
              Skriv afsnit
            </button>
          </div>
          <div class="edit-textArea">
            <div id="loading2" class="loading-screen" style="display: none">
              <div class="spinner"></div>
            </div>
            <label class="editor-header" for="intro-data">
              <span></span>
              <span class="edit-icons">
                <span class="speaker-icon">
                  <?xml version="1.0" encoding="utf-8"?>

                  <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                  <svg
                    version="1.0"
                    id="Layer_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="15px"
                    height="15px"
                    viewBox="0 0 64 64"
                    enable-background="new 0 0 64 64"
                    xml:space="preserve"
                  >
                    <g>
                      <path
                        fill="#ffff"
                        d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z"
                      />
                      <path
                        fill="#ffff"
                        d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z"
                      />
                      <path
                        fill="#ffff"
                        d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z"
                      />
                      <path
                        fill="#ffff"
                        d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z"
                      />
                      <path
                        fill="#ffff"
                        d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z"
                      />
                      <path
                        fill="#ffff"
                        d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z"
                      />
                    </g>
                  </svg>
                </span>
                <span class="copy-icon">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="15"
                    height="15"
                    viewBox="0 0 448 512"
                  >
                    <path
                      fill="white"
                      d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z"
                    />
                  </svg>
                </span>
              </span>
            </label>
            <textarea readonly name="intro-data" class="intro-data"></textarea>
          </div>
          <div class="remake-btn-div">
            <button onclick="clickButton(`btn3`)" class="other-btn">
              <a
                href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                style="text-decoration: none; color: white"
                >Omskriv teksten</a
              >
            </button>
          </div>
        </div>
        <div class="image-container">
          <img
            src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/31.png"
            alt=""
          />
        </div>
      </div>
    </div>
    <div class="heading-divider">
      <div>
        <hr />
      </div>
      <h2>Ansøgning</h2>
      <div>
        <hr />
      </div>
    </div>

    <!-- testing -->
    <div class="page-container">
      <div class="page-wrapper">
        <div class="edit-container">
          <h2>Forbedringsforslag til ansøgning</h2>
          <div class="edit-btns">
            <button id="btn4" class="start-btn" type="button">
              Skriv afsnit
            </button>
          </div>
          <div class="edit-textArea">
            <div id="loading3" class="loading-screen" style="display: none">
              <div class="spinner"></div>
            </div>
            <label class="editor-header" for="intro-data">
              <span></span>
              <span class="edit-icons">
                <span class="speaker-icon">
                  <?xml version="1.0" encoding="utf-8"?>

                  <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                  <svg
                    version="1.0"
                    id="Layer_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="15px"
                    height="15px"
                    viewBox="0 0 64 64"
                    enable-background="new 0 0 64 64"
                    xml:space="preserve"
                  >
                    <g>
                      <path
                        fill="#ffff"
                        d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z"
                      />
                      <path
                        fill="#ffff"
                        d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z"
                      />
                      <path
                        fill="#ffff"
                        d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z"
                      />
                      <path
                        fill="#ffff"
                        d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z"
                      />
                      <path
                        fill="#ffff"
                        d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z"
                      />
                      <path
                        fill="#ffff"
                        d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z"
                      />
                    </g>
                  </svg>
                </span>
                <span class="copy-icon">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="15"
                    height="15"
                    viewBox="0 0 448 512"
                  >
                    <path
                      fill="white"
                      d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z"
                    />
                  </svg>
                </span>
              </span>
            </label>
            <textarea readonly name="intro-data" class="intro-data"></textarea>
          </div>
          <div class="remake-btn-div">
            <button onclick="clickButton(`btn4`)" class="other-btn">
              <a
                href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                style="text-decoration: none; color: white"
                >Omskriv teksten</a
              >
            </button>
          </div>
        </div>
        <div class="image-container">
          <img
            src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/12.png"
            alt=""
          />
        </div>
      </div>
    </div>
    <div class="heading-divider" style="margin-top: 15px;">
      <div>
        <hr />
      </div>
      <h2>Udviklingsplan</h2>
      <div>
        <hr />
      </div>
    </div>
    <div class="page-container">
      <div class="page-wrapper">
        <div class="edit-container">
          <h2>Forslag til udvikling</h2>
          <div class="edit-btns">
            <button id="btn5" class="start-btn" type="button">
              Skriv afsnit
            </button>
          </div>
          <div class="edit-textArea">
            <div id="loading4" class="loading-screen" style="display: none">
              <div class="spinner"></div>
            </div>
            <label class="editor-header" for="intro-data">
              <span></span>
              <span class="edit-icons">
                <span class="speaker-icon">
                  <?xml version="1.0" encoding="utf-8"?>

                  <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                  <svg
                    version="1.0"
                    id="Layer_1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="15px"
                    height="15px"
                    viewBox="0 0 64 64"
                    enable-background="new 0 0 64 64"
                    xml:space="preserve"
                  >
                    <g>
                      <path
                        fill="#ffff"
                        d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z"
                      />
                      <path
                        fill="#ffff"
                        d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z"
                      />
                      <path
                        fill="#ffff"
                        d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z"
                      />
                      <path
                        fill="#ffff"
                        d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z"
                      />
                      <path
                        fill="#ffff"
                        d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z"
                      />
                      <path
                        fill="#ffff"
                        d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z"
                      />
                    </g>
                  </svg>
                </span>
                <span class="copy-icon">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="15"
                    height="15"
                    viewBox="0 0 448 512"
                  >
                    <path
                      fill="white"
                      d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z"
                    />
                  </svg>
                </span>
              </span>
            </label>
            <textarea readonly name="intro-data" class="intro-data"></textarea>
          </div>
          <div class="remake-btn-div">
            <button onclick="clickButton(`btn5`)" class="other-btn">
              <a
                href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                style="text-decoration: none; color: white"
                >Omskriv teksten</a
              >
            </button>
          </div>
        </div>
        <div class="image-container">
          <img
          style="margin-left: 51px;"
            src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/44.png"
            alt=""
          />
        </div>
      </div>
    </div>
    <div
      style="
        display: flex;
        flex-direction: row;
        justify-content: flex-start;
        padding: 2%;
        margin-top: 20px;
      "
    >
      <div style="align-self: center; align-items: center; align-self: center">
        <button
          type="button"
          class="pdf"
          onclick="writeNewApplication()"
          style="
            background-color: transparent;
            color: #33373d;
            font-size: 16px;
            border: none;
          "
        >
          Udarbejd en ny analyse
        </button>
      </div>
    </div>';

    load_bot_scripts();
    return $html;
}

add_shortcode('bot', 'bot');

function load_bot_scripts()
{

    wp_enqueue_style('bot-css', plugin_dir_url(__FILE__) . '/assets/css/bot.css');
    wp_enqueue_script('bot-js', plugin_dir_url(__FILE__) . '/assets/js/bot.js', array('jquery'), null, true);

}

//---------------------------------------------------------------------------------------------- WRITE_CV----------------------------------------------------------------------------

function write_cv()
{

    require ("prompts/write_cv_html.php");

    $cvPrompt = $_POST['cvPrompt'];
    $extraPrompt = $extraPrompts[intval($_POST['extraPrompt'])];
    ;
    $additionalPrompt = $_POST['additionalPrompt'];

    $messages = [
        ["role" => "user", "content" => "Skriv en introduktion"],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $extraPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.4,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_write_cv', 'write_cv');

function write_cv_html()
{

    require ("html/write_cv_html.php");
    load_cv();
    return $html;
}

add_shortcode("write_cv_html", "write_cv_html");
function load_cv()
{
    wp_enqueue_style('write_cv-css', SAC_PLUGIN_DIR . 'assets/css/write_cv.css');
    wp_enqueue_script('write_cv-js', SAC_PLUGIN_DIR . 'assets/js/write_cv.js', array(), '1.0.0', true);
}


//------------------------------------------------------------------feedback on application and cv limited ---------------------------------------------------------------------------


function application_cv_feedback()
{

    require ("prompts/application_cv_feedback_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];

    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response


}

add_action('wp_ajax_application_cv_feedback', 'application_cv_feedback');


function application_cv_feedback_html()
{
    require ("html/application_cv_feedback_html.php");
    load_application_cv_feedback();
    return $html;

}

add_shortcode("application_cv_feedback_html", "application_cv_feedback_html");

function load_application_cv_feedback()
{
    wp_enqueue_style('application_cv_feedback-css', SAC_PLUGIN_DIR . 'assets/css/application_cv_feedback.css');
    wp_enqueue_script('application_cv_feedback-js', SAC_PLUGIN_DIR . 'assets/js/application_cv_feedback.js', array('jquery'), true);
}


add_action('wp_ajax_tailor_made_gpt', 'tailor_made_gpt');


//---------------------------------------------------------------------- Taylor Made-----------------------------------------------------------------------------------------------------------------------------------
function tailor_made_gpt()
{
    // Check for WP Nonce for security (if you've passed one from your JS call)


    require ("prompts/tailor_made_html.php");
    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        //["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

function tailor_made_html()
{

    require ("html/tailor_made_html.php");
    load_tailor_made();
    return $html;
}

add_shortcode("tailor_made_html", "tailor_made_html");



function load_tailor_made()
{
    wp_enqueue_style('tailor_made-css', SAC_PLUGIN_DIR . 'assets/css/tailor_made.css');
    wp_enqueue_script('tailor_made-js', SAC_PLUGIN_DIR . 'assets/js/tailor_made.js', array('jquery'));
}

function app_cv_full_gpt()
{
    $prompts = [
        "Baseret på mit CV, ansøgning og det jobopslag jeg er interesseret i, bedes du skrive to afsnit. I det første afsnit skal du vurdere, hvor godt jeg matcher stillingen, og i det andet afsnit skal du identificere og fremhæve tre kompetencer, der er relevante for jobopslaget.
        
        
        
  Struktur for det første afsnit:

Overskrift: Min vurdering af matchet til stillingen
Giv en vurdering af, hvor godt jeg matcher stillingen ved at anvende betegnelserne 'Godt match', 'Moderat match' eller 'Lavt match'. Vær realistisk i din vurdering, og tag højde for, om jobopslaget kræver en specifik uddannelse. Du skal forsøge at være positiv, hvis det er et lavt match. 

Begrund herefter din vurdering af, hvordan jeg matcher stillingen.

[Lav mellemrum]

Overskrift: Fremhæv disse kompetencer
Vælg tre kompetencer fra mit CV og ansøgning, som jeg bør fremhæve i relation til jobopslaget og den profil, de søger. Oplist de 3 kompetencer i punktform, så det er let at læse. Forklar kort, hvorfor de er vigtige at nævne. 

Den samlede analyse skal være præcis og kort, maksimalt 600 tegn, og uden brug af formattering. Du skal skrive direkte til mig uden at bruge mit navn. Det er meget vigtigt at sproget er direkte, letforståeligt og jordnært, så det lyder naturligt for en dansktalende person. Undgå generisk tekst. Du skal derfor skrive i et naturligt og direkte sprog. 

Din samlede besked skal være 700 tegn eller kortere. 


",

        "Du skal skrive en personlig og målrettet indledning til mit CV, der matcher præcist med det job jeg søger. Indledningen skal både afspejle mine personlige og faglige kvaliteter, som matcher med jobopslaget og den ansøgning, jeg har skrevet. 
        
Sørg for at anvende en stil og tone, der føles oprigtig og jordnær, og som er naturligt for en dansktalende person. Teksten skal være unik og specifikt rettet den stilling, jeg søger. Undgå generiske floskler og sørg for, at sproget er direkte og letforståeligt.

Struktur:

1. Begynd dette afsnit direkte uden overskrift


Længde: Maksimum 300 characters eller mindre.
Indhold: Beskriv overordnet min faglige profil. Teksten skal indeholde konkrete eksempler på mine færdigheder og hvordan de har bidraget til mine tidligere roller. Fokuser på de mest relevante kvalifikationer i forhold til jobopslaget. Formålet med afsnittet er at overordnet matche min faglige profil og evner i forhold til jobopslaget. 

2. Overskrift: Derfor skal I vælge mig
[Lav mellemrum]

Længde: Maksimum 400 characters eller mindre.
Indhold: Kortlæg præcist, hvordan mine nøglekompetencer og personlige egenskaber matcher stillingsopslaget. Pas på det ikke bliver en gentagelse af mit CV, du skal tale om hvordan jeg konkret vil løfte opgaverne i jobopslaget på baggrund af min erfaring. Du skal være personlig og konkret, og fortælle hvad jeg har tænkt at gøre fremadrettet i stillingen. Husk at komme med konkrete eksempler.

Eksempeltekst til reference:
'Min motivation strækker sig ud over det rent faglige. Jeg brænder for at gøre en forskel i folks liv omkring mig og har en naturlig evne til at bygge relationer takket være min stærke empati. Dette kan blandt andet ses gennem mit arbejde som frivillig, som har været med til at styrke min evne til at møde borgeren i øjenhøjde. At arbejde selvstændigt er en af mine spidskompetencer, og jeg formår at prioritere og håndtere mine opgaver med omtanke.'

Det er vigtigt at du ikke gentager dig selv i de 2 afsnit. Det første afsnit beskriver min falige profil overordnet, hvor det næste afsnit beskriver specifict hvordan jeg vil løfte opgaverne i jobopslaget. Din samlede besked skal være 700 characters eller kortere. Skriv uden formattering inklusiv fed skrift i dit output og overskrifterne.



",

        "Din opgaven er at identificere og anbefale specifikke nøgleord som matcher med jobopslaget, som jeg kan indsætte i mit CV. Nøgleordene skal udvælges på baggrund af jobopslaget, mit CV og min ansøgning. Du skal kun udvælge nøgleord som jeg besidder. Du må ikke opdigte nøgleord jeg skal inkludere i mit CV, hvis ikke jeg besidder disse. 
        
        
        Struktur:

1. Overskrift: Brug disse nøgleord
[Lav mellemrum]

        -'Nøgleord 1'
        -'Nøgleord 2'
        
        
Skriv 10 nøgleord i alt. Vælg nøgleord, der direkte relaterer til de vigtigste krav og ønsker nævnt i jobopslaget. Disse kan inkludere:

Specifikke færdigheder (f.eks., 'projektledelse', 'kundeservice').
Teknologiske kompetencer (f.eks., 'Excel', 'Salesforce').
Branchespecifik jargon eller terminologi.
Personlige kvaliteter og kompetencer (f.eks., 'teamspiller', 'proaktiv').

2. Overskrift: Her skal du indsætte nøgleordende 
[Lav mellemrum]

Du skal fortælle mig meget specifikt hvor jeg skal indsætte hvert nyt nøgleord i mit CV. Det vil være svært for mig ellers at finde ud af hvor jeg skal indsætte de nøgleord du har fundet frem til. Nøgleordene skal kun indsættes i mit CV. Skriv uden formattering inklusiv fed skrift i dit output.

Du skal bruge mellemrum mellem hvert nøgleord i punktlisten, så det holdes overskueligt. Skriv uden formattering.

Det er herudover vigtigt at din analyse maksimalt fylder 800 characters! Skriv i et sprog som er direkte og naturligt at forstå for en dansktalende person.


",

        "Din opgave er at analysere og forbedre min jobansøgning for at sikre, at den er engagerende, relevant og matcher jobopslaget. Din analyse skal munde ud i konkrete, direkte anvendelige forbedringsforslag. Begrund dine forslag til forbedringer. Analysen skal være kort og må maksimalt fylde 1000 characters. Skriv uden formattering inklusiv fed skrift i dit output.
        
        
        Struktur:
        
        1. Overskrift: Forbedringforslag nummer 1
        
        
        Du skal udvælge første nøgleområder, hvor jeg skal forbedre ansøgningen. Du skal komme med konkrete eksempler på hvordan jeg kan omskrive teksten og en begrundelse for hvorfor jeg skal ændre det. Dine eksempler skal være meget konkrete, og skal kunne indsættes direkte i mmin ansøgning. Skriv uden formattering inklusiv fed skrift i dit output.
      
      2. Overskrift: Forbedringforslag nummer 2
        
        
        Du skal udvælge andet nøgleområder,  komme med konkrete eksempler på hvordan jeg kan omskrive teksten og en begrundelse for hvorfor jeg skal ændre det. Dine eksempler skal være meget konkrete, og skal kunne indsættes direkte i mmin ansøgning. Skriv uden formattering inklusiv fed skrift i dit output.
    
Skriv i et sprog som er direkte og naturligt at forstå for en dansktalende person. Fordi du kun må skrive 1000 characters, så det er vigtigt du kun udvælger det vigtigste jeg skal ændre i min ansøgning. Dit output skal være uden formattering. 

Eksempeltekst til reference:
'Min motivation strækker sig ud over det rent faglige. Jeg brænder for at gøre en forskel i folks liv omkring mig og har en naturlig evne til at bygge relationer takket være min stærke empati. Dette kan blandt andet ses gennem mit arbejde som frivillig, som har været med til at styrke min evne til at møde borgeren i øjenhøjde. At arbejde selvstændigt er en af mine spidskompetencer, og jeg formår at prioritere og håndtere mine opgaver med omtanke.

Din samlede besked skal være 1000 tegn eller kortere. 

",

        "Din opgave er at lave en specifik udviklingsplan, så jeg kan udvide mine kompetencer, og matche med jobopslaget bedre i fremtiden. Planen skal være specifik og handlingsorienteret. Du skal gennemgå mit CV, jobansøgning og stillingsopslaget for at se, hvor jeg mangler kompetencer.
        
        Denne plan skal detaljeret adressere hvilke specifikke færdigheder, erfaringer eller kurser jeg skal fokusere på for at optimere min kvalifikation til lignende stillinger i fremtiden. 
        
    Struktur:
    
    1. Overskrift: Sådan forbedre du dine faglige kompetencer
Identificer de vigtigste faglige kompetencer jeg mangler i forhold til stillingsopslaget. Kom med konkrete forslag til hvordan jeg kan forbedre disse. Du skal være så konkret som muligt, så jeg nemt kan handle på baggrund af dine forslag. 


2. Overskrift: Personlige kompetencer
Identificer de vigtigste personlige kompetencer jeg mangler i forhold til stillingsopslaget. Kom med konkrete forslag til hvordan jeg kan forbedre disse. Du skal være så konkret som muligt, så jeg nemt kan handle på baggrund af dine forslag. 


Husk at anvende mellemrum så teksten bliver overskuelig at læse. Skriv uden formattering inklusiv fed skrift i dit output og overskrifterne. Det er herudover vigtigt at din analyse maksimalt fylder 800 characters! Skriv i et sprog som er direkte og naturligt at forstå for en dansktalende person.

    
        
",
    ];
    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_app_cv_full_gpt', 'app_cv_full_gpt');


function app_cv_full_html()
{
    $html = '
  
  	 <div class="page-container insert-container">
        <div class="insert-inner-container">
            <div class="insert-common-container cv-container">
                <h2>Indsæt dit CV</h2>
                <textarea name="insert-cv" id="insert-cv" placeholder="Indsæt dit CV her..."></textarea>
                <div class="or-divider">
                    <hr class="divider-line" />
                    <span class="or-text">Eller</span>
                    <hr class="divider-line" />
                </div>
                <label class="label-head">Upload dit CV (kun i PDF-format)</label>
                <div class="custom">
                    <input type="file" class="form-control valid" type="text" name="pdf-input-cv" id="pdf-input-cv"
                        accept="application/pdf" required />
                </div>
            </div>
            <div class="insert-common-container job-container">
                <h2>Indsæt jobopslaget</h2>
                <textarea name="insert-jobDescription" id="insert-jobDescription"
                    placeholder="Indsæt jobopslaget her..."></textarea>
                <div class="or-divider">
                    <hr class="divider-line" />
                    <span class="or-text">Eller</span>
                    <hr class="divider-line" />
                </div>
                <label class="label-head">Upload jobopslaget (kun i PDF-format)</label>
                <div class="custom">
                    <input type="file" class="form-control valid" type="text" name="pdf-input-job" id="pdf-input-job"
                        accept="application/pdf" required />
                </div>
            </div>
            <div class="insert-addtional-container insert-common-container job-container">
                <h2>Indsæt ansøgning</h2>
                <textarea name="insert-addtional" id="insert-additonal"
                    placeholder="Indsæt din ansøgning her..."></textarea>
                <div class="or-divider">
                    <hr class="divider-line" />
                    <span class="or-text">Eller</span>
                    <hr class="divider-line" />
                </div>
                <label class="label-head">Upload ansøgningen (kun i PDF-format)</label>
                <div class="custom">
                    <input type="file" class="form-control valid" type="text" name="pdf-add-feild" id="pdf-add-feild"
                        accept="application/pdf" required />
                </div>
            </div>
        </div>
        <button style="
          background-color: #008080;
          border-style: solid;
          border-width: 1px 1px 1px 1px;
          border-color: #008080;
          box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.37);
          padding: 15px 20px 15px 20px;
          margin-bottom: 5%;
          border-radius: 15px;
        " onclick="clickStart()" type="button">
            Start analysen
        </button>
    </div>
    <div>
        <div class="page-container">
            <div class="page-wrapper">
                <div class="edit-container">
                    <h2>Overordnet analyse</h2>
                    <div class="edit-btns">
                        <button id="btn1" class="start-btn" type="button">
                            Skriv afsnit
                        </button>
                    </div>
                    <div class="edit-textArea">
                        <div id="loading0" class="loading-screen" style="display: none">
                            <div class="spinner"></div>
                        </div>
                        <label class="editor-header" for="intro-data">
                            <span></span>
                            <span class="edit-icons">
                                <span class="speaker-icon">
                                    <?xml version="1.0" encoding="utf-8"?>

                                    <!DOCTYPE svg
                                        PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                                    <svg version="1.0" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                        xmlns:xlink="http://www.w3.org/1999/xlink" width="15px" height="15px"
                                        viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve">
                                        <g>
                                            <path fill="#ffff"
                                                d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z" />
                                            <path fill="#ffff"
                                                d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z" />
                                            <path fill="#ffff"
                                                d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z" />
                                            <path fill="#ffff"
                                                d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z" />
                                            <path fill="#ffff" d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z" />
                                            <path fill="#ffff" d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z" />
                                        </g>
                                    </svg>
                                </span>
                                <span class="copy-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                        viewBox="0 0 448 512">
                                        <path fill="white"
                                            d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z" />
                                    </svg>
                                </span>
                            </span>
                        </label>
                        <textarea readonly name="intro-data" class="intro-data"></textarea>
                    </div>
                    <div class="remake-btn-div">
                        <button onclick="clickButton("btn1")" class="other-btn">
                            <a href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                                style="text-decoration: none; color: white">Omskriv teksten</a>
                        </button>
                    </div>
                </div>
                <div class="image-container">
                    <img style="margin-left: 0px;"
                        src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/Screenshot-2024-02-04-084937-1.png"
                        alt="" />
                </div>
            </div>
        </div>
    </div>
    <div class="heading-divider">
        <div>
            <hr />
        </div>
        <h2>CV</h2>
        <div>
            <hr />
        </div>
    </div>

    <div class="page-container">
        <div class="page-wrapper">
            <div class="edit-container">
                <h2>CV-tilpasning: Skræddersyet indledning</h2>
                <div class="edit-btns">
                    <button id="btn2" class="start-btn" type="button">
                        Skriv afsnit
                    </button>
                </div>
                <div class="edit-textArea">
                    <div id="loading1" class="loading-screen" style="display: none">
                        <div class="spinner"></div>
                    </div>
                    <label class="editor-header" for="intro-data">
                        <span></span>
                        <span class="edit-icons">
                            <span class="speaker-icon">
                                <?xml version="1.0" encoding="utf-8"?>

                                <!DOCTYPE svg
                                    PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                                <svg version="1.0" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                    xmlns:xlink="http://www.w3.org/1999/xlink" width="15px" height="15px"
                                    viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve">
                                    <g>
                                        <path fill="#ffff"
                                            d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z" />
                                        <path fill="#ffff"
                                            d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z" />
                                        <path fill="#ffff"
                                            d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z" />
                                        <path fill="#ffff"
                                            d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z" />
                                        <path fill="#ffff" d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z" />
                                        <path fill="#ffff" d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z" />
                                    </g>
                                </svg>
                            </span>
                            <span class="copy-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 448 512">
                                    <path fill="white"
                                        d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z" />
                                </svg>
                            </span>
                        </span>
                    </label>
                    <textarea readonly name="intro-data" class="intro-data"></textarea>
                </div>
                <div class="remake-btn-div">
                    <button onclick="clickButton("btn2")" class="other-btn">
                        <a href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                            style="text-decoration: none; color: white">Omskriv teksten</a>
                    </button>
                </div>
            </div>
            <div class="image-container">
                <img src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/342.png" alt="" />
            </div>
        </div>
    </div>
    <div>
        <div class="page-container">
            <div class="page-wrapper">
                <div class="edit-container">
                    <h2>CV-tilpasning: Indsæt vigtige nøgleord</h2>
                    <div class="edit-btns">
                        <button id="btn3" class="start-btn" type="button">
                            Skriv afsnit
                        </button>
                    </div>
                    <div class="edit-textArea">
                        <div id="loading2" class="loading-screen" style="display: none">
                            <div class="spinner"></div>
                        </div>
                        <label class="editor-header" for="intro-data">
                            <span></span>
                            <span class="edit-icons">
                                <span class="speaker-icon">
                                    <?xml version="1.0" encoding="utf-8"?>

                                    <!DOCTYPE svg
                                        PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                                    <svg version="1.0" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                        xmlns:xlink="http://www.w3.org/1999/xlink" width="15px" height="15px"
                                        viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve">
                                        <g>
                                            <path fill="#ffff"
                                                d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z" />
                                            <path fill="#ffff"
                                                d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z" />
                                            <path fill="#ffff"
                                                d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z" />
                                            <path fill="#ffff"
                                                d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z" />
                                            <path fill="#ffff" d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z" />
                                            <path fill="#ffff" d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z" />
                                        </g>
                                    </svg>
                                </span>
                                <span class="copy-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                        viewBox="0 0 448 512">
                                        <path fill="white"
                                            d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z" />
                                    </svg>
                                </span>
                            </span>
                        </label>
                        <textarea readonly name="intro-data" class="intro-data"></textarea>
                    </div>
                    <div class="remake-btn-div">
                        <button onclick="clickButton("btn3")" class="other-btn">
                            <a href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                                style="text-decoration: none; color: white">Omskriv teksten</a>
                        </button>
                    </div>
                </div>
                <div class="image-container">
                    <img src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/31.png" alt="" />
                </div>
            </div>
        </div>
    </div>
    <div class="heading-divider">
        <div>
            <hr />
        </div>
        <h2>Ansøgning</h2>
        <div>
            <hr />
        </div>
    </div>

    <!-- testing -->
    <div>
        <div class="page-container">
            <div class="page-wrapper">
                <div class="edit-container">
                    <h2>Forbedringsforslag til ansøgning</h2>
                    <div class="edit-btns">
                        <button id="btn4" class="start-btn" type="button">
                            Skriv afsnit
                        </button>
                    </div>
                    <div class="edit-textArea">
                        <div id="loading3" class="loading-screen" style="display: none">
                            <div class="spinner"></div>
                        </div>
                        <label class="editor-header" for="intro-data">
                            <span></span>
                            <span class="edit-icons">
                                <span class="speaker-icon">
                                    <?xml version="1.0" encoding="utf-8"?>

                                    <!DOCTYPE svg
                                        PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                                    <svg version="1.0" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                        xmlns:xlink="http://www.w3.org/1999/xlink" width="15px" height="15px"
                                        viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve">
                                        <g>
                                            <path fill="#ffff"
                                                d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z" />
                                            <path fill="#ffff"
                                                d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z" />
                                            <path fill="#ffff"
                                                d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z" />
                                            <path fill="#ffff"
                                                d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z" />
                                            <path fill="#ffff" d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z" />
                                            <path fill="#ffff" d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z" />
                                        </g>
                                    </svg>
                                </span>
                                <span class="copy-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                        viewBox="0 0 448 512">
                                        <path fill="white"
                                            d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z" />
                                    </svg>
                                </span>
                            </span>
                        </label>
                        <textarea readonly name="intro-data" class="intro-data"></textarea>
                    </div>
                    <div class="remake-btn-div">
                        <button onclick="clickButton("btn4")" class="other-btn">
                            <a href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                                style="text-decoration: none; color: white">Omskriv teksten</a>
                        </button>
                    </div>
                </div>
                <div class="image-container">
                    <img src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/12.png" alt="" />
                </div>
            </div>
        </div>
    </div>
    <div class="heading-divider" style="margin-top: 15px;">
        <div>
            <hr />
        </div>
        <h2>Udviklingsplan</h2>
        <div>
            <hr />
        </div>
    </div>
    <div>
        <div class="page-container">
            <div class="page-wrapper">
                <div class="edit-container">
                    <h2>Forslag til udvikling</h2>
                    <div class="edit-btns">
                        <button id="btn5" class="start-btn" type="button">
                            Skriv afsnit
                        </button>
                    </div>
                    <div class="edit-textArea">
                        <div id="loading4" class="loading-screen" style="display: none">
                            <div class="spinner"></div>
                        </div>
                        <label class="editor-header" for="intro-data">
                            <span></span>
                            <span class="edit-icons">
                                <span class="speaker-icon">
                                    <?xml version="1.0" encoding="utf-8"?>

                                    <!DOCTYPE svg
                                        PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">

                                    <svg version="1.0" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                        xmlns:xlink="http://www.w3.org/1999/xlink" width="15px" height="15px"
                                        viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve">
                                        <g>
                                            <path fill="#ffff"
                                                d="M61,29H49c-1.657,0-3,1.344-3,3s1.343,3,3,3h12c1.657,0,3-1.344,3-3S62.657,29,61,29z" />
                                            <path fill="#ffff"
                                                d="M59.312,44.57l-11.275-4.104c-1.559-0.566-3.279,0.236-3.846,1.793c-0.566,1.555,0.235,3.277,1.793,3.844
                                        l11.276,4.105c1.558,0.566,3.278-0.238,3.845-1.793C61.672,46.859,60.87,45.137,59.312,44.57z" />
                                            <path fill="#ffff"
                                                d="M48.036,23.531l11.276-4.104c1.557-0.566,2.359-2.289,1.793-3.843c-0.566-1.558-2.288-2.362-3.846-1.796
                                        l-11.275,4.106c-1.559,0.566-2.36,2.289-1.794,3.846C44.757,23.295,46.479,24.098,48.036,23.531z" />
                                            <path fill="#ffff"
                                                d="M8,48c1.257,0,2.664,0,4,0V16c-1.342,0.002-2.747,0.002-4,0.002V48z" />
                                            <path fill="#ffff" d="M0,20.002V44c0,2.211,1.789,4,4,4c0,0,0.797,0,2,0V16.002c-1.204,0-2,0-2,0C1.789,16.002,0,17.791,0,20.002
                                        z" />
                                            <path fill="#ffff" d="M37.531,0.307c-1.492-0.625-3.211-0.277-4.359,0.867L18.859,15.486c0,0-0.422,0.515-1.359,0.515
                                        c-0.365,0-1.75,0-3.5,0v32c1.779,0,3.141,0,3.344,0c0.656,0,1.107,0.107,1.671,0.67c0.563,0.564,14.157,14.158,14.157,14.158
                                        C33.938,63.594,34.961,64,36,64c0.516,0,1.035-0.098,1.531-0.305C39.027,63.078,40,61.617,40,60V4.002
                                        C40,2.385,39.027,0.924,37.531,0.307z" />
                                        </g>
                                    </svg>
                                </span>
                                <span class="copy-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                        viewBox="0 0 448 512">
                                        <path fill="white"
                                            d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56h168zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24zm120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97z" />
                                    </svg>
                                </span>
                            </span>
                        </label>
                        <textarea readonly name="intro-data" class="intro-data"></textarea>
                    </div>
                    <div class="remake-btn-div">
                        <button onclick="clickButton("btn5")" class="other-btn">
                            <a href="#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3DeyJpZCI6IjYxMjUiLCJ0b2dnbGUiOmZhbHNlfQ%3D%3D"
                                style="text-decoration: none; color: white">Omskriv teksten</a>
                        </button>
                    </div>
                </div>
                <div class="image-container">
                    <img style="margin-left: 51px;"
                        src="https://app.ansogningshjaelpen.dk/wp-content/uploads/2024/02/44.png" alt="" />
                </div>
            </div>
        </div>
    </div>
  ';

    load_app_cv_full();
    return $html;
}

function load_app_cv_full()
{
    wp_enqueue_style('app_cv_full-css', SAC_PLUGIN_DIR . 'assets/css/app_cv_full.css');
    wp_enqueue_script('app_cv_full-js', SAC_PLUGIN_DIR . 'assets/js/app_cv_full.js', array('jquery'), true);
}


//------------------------------------------------------------- COmpany internship full ------------------------------------------------------------------------------------------------------------
function internship_full_gpt()
{
    require ("prompts/internship_full_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_internship_full_gpt', 'internship_full_gpt');

function internship_full_html()
{

    require ("html/internship_full_html.php");
    load_internship_full();
    return $html;
}

add_shortcode("internship_full_html", "internship_full_html");
function load_internship_full()
{
    wp_enqueue_style('internship_full-css', SAC_PLUGIN_DIR . 'assets/css/internship_full.css');
    wp_enqueue_script('internship_full-js', SAC_PLUGIN_DIR . 'assets/js/internship_full.js', array('jquery'), true);
}



//--------------------------------Company Internship Limited-----------------------------------------------------------------------------------------------------------
function internship_limited_html()
{

    require ("html/internship_limited_html.php");
    load_internship_limited();
    return $html;
}

add_shortcode("internship_limited_html", "internship_limited_html");
function load_internship_limited()
{
    wp_enqueue_style('internship_limited-css', SAC_PLUGIN_DIR . 'assets/css/internship_limited.css');
    wp_enqueue_script('internship_limited-js', SAC_PLUGIN_DIR . 'assets/js/internship_limited.js', array('jquery'), true);
}


function internship_limited_gpt()
{

    require ("prompts/internship_limited_html.php");


    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_internship_limited_gpt', 'internship_limited_gpt');

//------------------------------------------------------------------------------------ Standard App full English----------------------------------------------------------------------------------------------------------

function standard_app_full_gpt()
{


    require ("prompts/standard_app_full_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_standard_app_full_gpt', 'standard_app_full_gpt');


function standard_app_full_html()
{


    require ("html/standard_app_full_html.php");
    load_standard_app_full();
    return $html;
}


add_shortcode("standard_app_full_html", "standard_app_full_html");
function load_standard_app_full()
{
    wp_enqueue_style('standard_app_full-css', SAC_PLUGIN_DIR . 'assets/css/standard_app_full.css');
    wp_enqueue_script('standard_app_full-js', SAC_PLUGIN_DIR . 'assets/js/standard_app_full.js', array('jquery'), true);
}




//------------------------------------------------------------------------------------ Standard App limited English----------------------------------------------------------------------------------------------------------

function standard_app_limited_gpt()
{


    require ("prompts/standard_app_limited_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];


    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_standard_app_limited_gpt', 'standard_app_limited_gpt');




function standard_app_limited_html()
{

    require ("html/standard_app_limited_html.php");
    load_standard_app_limited();
    return $html;

}

add_shortcode("standard_app_limited_html", "standard_app_limited_html");

function load_standard_app_limited()
{
    wp_enqueue_style('standard_app_limited-css', SAC_PLUGIN_DIR . 'assets/css/standard_app_limited.css');
    wp_enqueue_script('standard_app_limited-js', SAC_PLUGIN_DIR . 'assets/js/standard_app_limited.js', array('jquery'), true);

}


//------------------------------------------------------------------------------------ Unsolicited App limited----------------------------------------------------------------------------------------------------------
function unsolicited_app_limited_gpt()
{


    require ("prompts/unsolicited_app_limited_html.php");
    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action("wp_ajax_unsolicited_app_limited_gpt", "unsolicited_app_limited_gpt");


function unsolicited_app_limited_html()
{

    require ("html/unsolicited_app_limited_html.php");
    load_unsolicited_app_limited();
    return $html;
}


add_shortcode("unsolicited_app_limited_html", "unsolicited_app_limited_html");


function load_unsolicited_app_limited()
{
    wp_enqueue_style('unsolicited_app_limited-css', SAC_PLUGIN_DIR . 'assets/css/unsolicited_app_limited.css');
    wp_enqueue_script('unsolicited_app_limited-js', SAC_PLUGIN_DIR . 'assets/js/unsolicited_app_limited.js', array('jquery'), true);
}


//------------------------------------------------------------------------------------Quota 2 limited----------------------------------------------------------------------------------------------------------

function quota2_limited_gpt()
{

    require ("prompts/quota2_limited_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action("wp_ajax_quota2_limited_gpt", "quota2_limited_gpt");


function quota2_limited_html()
{

    require ("html/quota2_limited_html.php");
    load_quota2();
    return $html;
}

add_shortcode("quota2_limited_html", "quota2_limited_html");
function load_quota2()
{
    wp_enqueue_style('quota2_limited-css', SAC_PLUGIN_DIR . 'assets/css/quota2_limited.css');
    wp_enqueue_script('quota2_limited-js', SAC_PLUGIN_DIR . 'assets/js/quota2_limited.js', array('jquery'), true);
}

//------------------------------------------------------------------------------------ Standard App full non English----------------------------------------------------------------------------------------------------------
function standard_app_full_non_english_gpt()
{


    require ("prompts/standard_app_full_non_english_html.php");
    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response

}

add_action("wp_ajax_standard_app_full_non_english_gpt", "standard_app_full_non_english_gpt");



function standard_app_full_non_english_html()
{



    require ("html/standard_app_full_non_english_html.php");
    load_standard_app_full_non_english();
    return $html;

}

add_shortcode("standard_app_full_non_english_html", "standard_app_full_non_english_html");


function load_standard_app_full_non_english()
{
    wp_enqueue_style('standard_app_full_non_english-css', SAC_PLUGIN_DIR . 'assets/css/standard_app_full_non_english.css');
    wp_enqueue_script('standard_app_full_non_english-js', SAC_PLUGIN_DIR . 'assets/js/standard_app_full_non_english.js', array('jquery'), true);

}

//------------------------------------------------------------------------------------Quota 2 full ----------------------------------------------------------------------------------------------------------
function quota2_full_gpt()
{
    require ("prompts/quota2_full_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.4,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response

}

add_action("wp_ajax_quota2_full_gpt", "quota2_full_gpt");

function quota2_full_html()
{

    require ("html/quota2_full_html.php");
    load_quota2_full();
    return $html;

}

add_shortcode("quota2_full_html", "quota2_full_html");


function load_quota2_full()
{
    wp_enqueue_style('quota2_full-css', SAC_PLUGIN_DIR . 'assets/css/quota2_full.css');
    wp_enqueue_script('quota2_full-js', SAC_PLUGIN_DIR . 'assets/js/quota2_full.js', array('jquery'), true);
}


//------------------------------------------------------------------------------------ Unsolicited App full----------------------------------------------------------------------------------------------------------
function unsolicited_application_full_gpt()
{

    require ("prompts/unsolicited_application_full_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response

}

add_action("wp_ajax_unsolicited_application_full_gpt", "unsolicited_application_full_gpt");



function unsolicited_application_full_html()
{

    require ("html/unsolicited_application_full_html.php");
    load_unsolicited_application_full();
    return $html;

}

add_shortcode("unsolicited_application_full_html", "unsolicited_application_full_html");

function load_unsolicited_application_full()
{
    wp_enqueue_style('unsolicited_application_full-css', SAC_PLUGIN_DIR . 'assets/css/unsolicited_application_full.css');
    wp_enqueue_script('unsolicited_application_full-js', SAC_PLUGIN_DIR . 'assets/js/unsolicited_application_full.js', array('jquery'), true);
}



//------------------------------------------------------------------------------------ Standard App full Non_English----------------------------------------------------------------------------------------------------------

function standard_application_limited_non_english_gpt()
{

    require ("prompts/standard_application_full_non_english_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response

}

add_action("wp_ajax_standard_application_limited_non_english_gpt", "standard_application_limited_non_english_gpt");



function standard_application_limited_non_english_html()
{

    require ("html/standard_application_limited_non_english_html.php");
    load_standard_application_limited_non_english();
    return $html;

}
//======================================================================================== THE FILE NAME IS STANDARD_APPLICATION_LIMITED_NOT_ENGLISH_HTML BUT THE BOT IS OF FULL =====================================================================================
add_shortcode("standard_application_full_non_english_html", "standard_application_limited_non_english_html");

function load_standard_application_limited_non_english()
{

    wp_enqueue_style('standard_application_limited_non_english-css', SAC_PLUGIN_DIR . 'assets/css/standard_application_limited_non_english.css');
    wp_enqueue_script('standard_application_limited_non_english-js', SAC_PLUGIN_DIR . 'assets/js/standard_application_limited_non_english.js', array('jquery'), null, true);
}


function test_seperate_fle()
{
    require "temp.php";
    return $content;
}
add_shortcode("test", "test_seperate_fle");


//------------------------------------------------------------------------------------ Standard App feedback limited Non_English----------------------------------------------------------------------------------------------------------
function standard_application_feedback_non_english_limited_gpt()
{

    require ("prompts/standard_application_feedback_non_english_limited_html.php");

    $prompt = $prompts[intval($_POST['prompt'])];
    $cvPrompt = $_POST['cvPrompt'];
    $jobDescriptionPrompt = $_POST['jobDescriptionPrompt'];
    $additionalPrompt = $_POST['additionalPrompt'];


    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $cvPrompt],
        ["role" => "user", "content" => $jobDescriptionPrompt],
        ["role" => "user", "content" => $additionalPrompt],
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            // Never hardcode your secrets in the code. Retrieve them from a secure place.
            'Authorization' => 'Bearer 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',
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.5,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500, // Increase timeout to 30 seconds
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // this is required to terminate immediately and return a proper response

}

add_action("wp_ajax_standard_application_feedback_non_english_limited_gpt", "standard_application_feedback_non_english_limited_gpt");



function standard_application_feedback_non_english_limited_html()
{

    require ("html/standard_application_feedback_non_english_limited_html.php");
    load_standard_application_feedback_non_english_limited_html();
    return $html;

}

add_shortcode("standard_application_feedback_non_english_limited_html", "standard_application_feedback_non_english_limited_html");

function load_standard_application_feedback_non_english_limited_html()
{

    wp_enqueue_style('standard_application_feedback_non_english_limited_html-css', SAC_PLUGIN_DIR . 'assets/css/standard_application_feedback_non_english_limited_html.css');
    wp_enqueue_script('standard_application_feedback_non_english_limited_html-js', SAC_PLUGIN_DIR . 'assets/js/standard_application_feedback_non_english_limited_html.js', array('jquery'));
}




//--------------------------------------------- Redesign ------------------------------------- 

function redesign_application()
{

    $html = '
  
  	<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cover Letter Bot</title>
    <meta name="description"
        content="Create professional cover letters effortlessly with our Cover Letter Bot. Easy to use, with tips and pricing information.">
    <!-- <link rel="stylesheet" href="./style.css"> -->

    <style>
        :root {
            --primary-color: #5c6bc0;
            --secondary-color: #e0e0e0;
            --tertiary-color: #f4f7f6;
            --gradient-color: linear-gradient(to right, #385bd0, #354987);
            --upload-button-color: #f0eeff;

            --blue-violet: #913dff;
            --next-button-color: linear-gradient(to bottom, var(--blue-violet), #7733f4);

            --builder-header: #3e4b6d;
            --font: #3e4b6d;
            --use-this-template: linear-gradient(to bottom, var(--builder-header), var(--font));
            ;
        }

        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 0;
        }


        .navbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;

            padding: 10px 20px;

        }

        .navbar h1 {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }

        .navbar .nav-links {
            display: flex;
            gap: 20px;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            padding: 8px 16px;
            transition: background-color 0.3s ease;
        }

        .navbar .nav-links a:hover {
            background-color: #ddd;
            border-radius: 5px;
        }

        .navbar .nav-links .button {
            background-image: var(--next-button-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            transition: background-color 0.3s ease;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .navbar .nav-links .button:hover {
            background-color: #0056b3;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
button:disabled, button:disabled:hover {
  /* Styling for disabled button */
  background-color: #cccccc; /* Light gray */
  color: white; /* Text color */
  border: 1px solid #cccccc; /* Border color */
  transform: none; /* Ensure no transform is applied */
  cursor: not-allowed; /* Change cursor to indicate the button is disabled */
}


        /*------------------------------------------------------- first page -------------------------------------------------  */


        .onboarding-container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            margin-top: 20px;
        }

        .onboarding-container h1 {
            font-size: 15px;
        }

        .onboarding-container h2 {
            margin: 40px 0 0 0;
        }

        .progress-bar {
            height: 7px;
            background: #e0e0e0;
            border-radius: 5px;
            margin: 10px 0;
        }

        .progress {
            height: 100%;
            background: black;
            border-radius: 5px;
            width: 0%;
            /* Start with 0% width */
            animation: loadProgress 2s ease forwards;
            /* Animation name, duration, easing, and forwards to retain the end state */
        }

        @keyframes loadProgress {
            0% {
                width: 0%;
            }

            100% {
                width: 33%;
            }
        }


        .heading-section p {

            color: #666;
        }



        section p {

            color: #666;
        }

        .upload-section {
            display: flex;
            justify-content: space-between;
            margin: 40px 0 0 0;
        }

        .upload-option {
            width: 48%;
        }

        .upload-option p {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: black;
            margin-bottom: 8px;

        }

        .upload-button {
            background-color: var(--upload-button-color);
            width: 100%;
            padding: 10px;
            margin-bottom: 5px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 11px;
            box-shadow: 0 1px 1px rgba(103, 103, 103, 0.2);
            transition: transform 0.5 ease;
        }

        .upload-button:hover {
            transform: scale(1.02);
        }


        .upload-option .Or-span {
            display: block;
            text-align: center;
            margin: 25px 0;
        }

        .showFile {
            box-sizing: border-box;
            width: 100%;
            padding: 10px 20px;
            margin-bottom: 5px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 11px;
            background-color: #EFEFEF;
            box-shadow: 0 1px 1px rgba(103, 103, 103, 0.2);
        }

        .upload-option textarea {
            width: 100%;
            padding: 10px;
            height: 130px;
            resize: none;
            box-sizing: border-box;
            border-radius: 10px;
            outline: 0.5px solid rgb(164, 163, 163);
            border: none;
            box-shadow: 0 1px 1px rgba(103, 103, 103, 0.2);
        }



        .upload-option textarea:focus {
            outline: 1px solid black;

        }

        .textarea-with-button-container {
            position: relative;
            display: inline-block;
            /* Adjust depending on layout needs */
            width: 100%;
            margin-bottom: 20px;
            /* Space for after the container */
        }

        #cv-textarea-container textarea {
            width: 100%;
            padding: 10px;
            height: 130px;
            /* Adjust height as needed to fit content and button */
            resize: none;
            box-sizing: border-box;
            border-radius: 10px;
            outline: 0.5px solid rgb(164, 163, 163);
            border: 1px solid #ccc;
            /* Mimic border to match other textareas */
            box-shadow: 0 1px 1px rgba(103, 103, 103, 0.2);
            
            padding-bottom: 45px;
            
        }

        #cv-textarea-container .textarea-button {
            position: absolute;
            bottom: 10px;
            /* Adjust to position the button correctly within the container */
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 15px;
            border: 2px dashed grey;
            /* Dashed grey border */
            border-radius: 5px;
            background-color: transparent;
            /* Transparent background */
            color: grey;
            /* Match the border color for seamless design */
            cursor: pointer;
            font-size: 14px;
            /* Adjust font size as needed */
            text-align: center;
            transition: all 0.3s ease;
            /* Smooth transition for hover effects */
        }

        #cv-textarea-container .textarea-button:hover {
            background-color: rgba(0, 0, 0, 0.1);
            /* Slight highlight on hover */
            color: black;
            /* Darken text to indicate interactivity */
            border-color: black;
            /* Darken border on hover for visibility */
        }


        #cv-textarea-container textarea:focus {
            outline: 1px solid black;
            /* Maintain focus style */
        }


        /*----------------------------------------------------cv dropdown-------------------------------  */
        .next-btn-div {
            display: flex;
            justify-content: end;
        }

        .next-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            background-image: var(--next-button-color);
            padding: 12px 40px;
            color: white;
            border: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: transform 0.1s ease, box-shadow 0.3s ease, filter 0.3s ease;
            /* Transition for shadow and filter */
            /* Initial shadow with slightly more visibility */
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
        }

        .next-button:hover {
            /* Increase the brightness on hover */
            filter: brightness(1.1);
            /* More pronounced shadow animation for visibility */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .next-button:active {
            transform: scale(0.95);
            /* Scale down slightly on click */
        }




        .back-button {
            width: 95px;
            padding: 12px 40px;
            background-color: #007bff;
            color: rgb(255, 255, 255);
            border: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: transform 0.1s ease, box-shadow 0.3s ease;
            /* Smooth transitions for shadow and press effect */
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
            /* Initial subtle shadow for depth */
            display: flex;
            justify-content: center;
            background-color: var(--upload-button-color);
            color: blue;
        }

        .back-button:hover {
            /* background-color: #3f51b5; */
            /* Enhanced visible shadow for hover state */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .back-button:active {
            transform: scale(0.95);
            /* Press down animation on click */
        }


        /*--------------------------------------------------------------- Page-2 -------------------------------------------------------------------- */
        .template {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 40px;
        }
        
        .template : hover {
        	background-color: "light-grey";
        }

        .template img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            max-height: 100%;
        }

        .template .content button {
            padding: 10px;
            display: block;
            width: 100%;
            border: none;
            background-image: var(--use-this-template);
            background-color: #EFEFEF;
            color: rgb(255, 255, 255);
            border-radius: 8px;
            cursor: pointer;
            max-width: -moz-fit-content;
            max-width: fit-content;
            max-height: -moz-fit-content;
            max-height: fit-content;
        }

        .template .content {
            margin: 0;
        }

        .template .content h2 {
            font-size: 1.1em;
            margin-bottom: 5px;
            margin-top: 1px;
            font-weight: 700;
        }

        .template .content h3 {
            color: #a1a5b9;
            font-size: 0.9em;
            margin-bottom: 5px;
            margin-top: 5px;
            font-weight: 300;
        }

        .template .content p {
            color: #a1a5b9;
            font-size: 1.1em;
            /* margin-bottom: 5px; */
            margin-top: 1px;
            font-weight: 300;
        }

        .template button:hover {
            background-color: #3f51b5;
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            text-align: right;
            margin-right: 2%;
        }

        .navigation-buttons button.skip-button {
            width: -moz-fit-content;
            width: fit-content;
            padding: 12px;
            border: none;
            background-color: #EFEFEF;
            color: rgb(0, 0, 0);
            border-radius: 5px;
            cursor: pointer;
            font-weight: 700;
            margin-right: 10px;
        }

        .navigation-buttons button.primary {
            background-color: #808ee1;
            color: white;
            padding-left: 3%;
            padding-right: 3%;
        }

        .navigation-buttons button.primary:hover {
            background-color: #3f51b5;
        }
        
        
        /* Loader CSS */
        .textarea-container.loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border-radius: 50%;
            border: 2px solid #ccc;
            border-top-color: #333;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .textarea-container {
            position: relative;
        }



        /* ------------------------------------- Page 3 -------------------------------------------------- */

        .get-started-p {
            margin-bottom: 40px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section label {
            font-size: 14px;
            font-weight: bold;
            color: black;
            margin-bottom: 8px;
        }

        .form-section .textarea-container {
            position: relative;
            width: 100%;
        }

        .form-section textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            height: 200px;
            resize: none;
            box-sizing: border-box;
            border-radius: 10px;
            box-shadow: 0 1px 1px rgba(103, 103, 103, 0.2);
            
            padding-right: 34px;
        }

        .form-section .info-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            transition: opacity 0.3s ease;
            /* Smooth transition for the opacity change */
        }

        /* Hide the icon when the textarea is focused */
        .form-section .textarea-container:focus-within .info-icon {
            opacity: 0;
            visibility: hidden;
        }



        .form-section button {
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            margin-top: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-image: linear-gradient(to right, #385bd0, #e14e42, #f9d423);
            background-size: 300% 100%;
            background-position: 0% 50%;
            transition: background-position 1s ease, background-size 1s ease, transform 0.1s ease;
            /* Smooth transition for the transform effect */
        }

        .form-section button:hover {
            background-position: 100% 50%;
            background-size: 200% 100%;
        }

        .form-section button:active {

            transform: scale(0.95);
            /* Button scales down when clicked */
        }



        /* Optional: Keyframes for a pulsating effect */
        @keyframes pulseAnimation {
            0% {
                background-size: 200% 100%;
            }

            50% {
                background-size: 250% 100%;
            }

            100% {
                background-size: 200% 100%;
            }
        }

        .form-section button:hover {
            animation: pulseAnimation 2s ease infinite;
        }


        .dotted {
            border-bottom: 2px dotted #ccc;
            margin: 20px 0;
        }

        .footer-buttons {
            display: flex;
            justify-content: space-between;
        }

        .footer-buttons button {
            width: calc(50% - 10px);
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #f4f7f6;
            cursor: pointer;
            /* Smooth transition for the press effect */
            transition: transform 0.1s ease;
        }

        .footer-buttons button:active {
            /* Scales down the button when clicked, giving a pressed effect */
            transform: scale(0.95);
        }

        /* You might have additional hover styles */
        .footer-buttons button:hover {
            /* Example hover effect - adjust or remove according to your design */
            background-color: #e2e6e9;
        }





        .footer-buttons button.primary {
            background-color: #5c6bc0;
            color: white;
            border: none;
        }

        .footer-buttons button.primary:hover {
            background-color: #3f51b5;
        }

        /* General button styling for a professional look */
        .view-button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            font-size: 16px;
            color: #fff;
            background-color: #007bff;
            /* Bootstrap primary color */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .view-button:hover {
            background-color: #0056b3;
            /* Darker shade for hover effect */
        }

        /* Styling for the container to position the button over the image */
        .image {
            position: relative;
            display: inline-block;
        }

        /* Position the button at the bottom-right of the image */
        /* Adjusted button styling for transparency */
        .view-button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            font-size: 16px;
            color: #fff;
            background-color: rgba(0, 123, 255, 0.7);
            /* Blue with transparency */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .view-button:hover {
            background-color: rgba(0, 86, 179, 0.7);
            /* Darker blue on hover with the same transparency */
        }


        
        .image img {
            display: block;
            width: 100%;
            /* or specific width, depending on your layout */
            height: 150px;
            cursor: zoom-in;
        }

        #lightbox {
        	z-index: 10000;
            position: fixed;
            /* Full viewport overlay */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            /* Semi-transparent black background */

            justify-content: center;
            /* Center horizontally */
            align-items: center;
            /* Center vertically */
            cursor: pointer;
            /* Optional: Changes the cursor to indicate the overlay can be clicked */
        }


        #lightboxClose {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 30px;
            color: #fff;
        }

        #lightbox img {
            max-width: 80%;
        
            max-height: 80%;
        
            object-fit: contain;
        
        }




        .lightbox {
            display: none;
            position: fixed;
            z-index: 2;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .lightbox-content {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .lightbox-content img {
            /* max-width: 100%; */
            /* Ensures image is not wider than the screen */
            /* max-height: 100vh; */
            /* Ensures image is not taller than the screen */
            /* display: block; */
            /* Remove extra space below the image */
            margin: auto;
            width: 80%;
            height: 90%;
            /* Center the image horizontally in the lightbox */
        }

        .selected-template {
            border: 2px solid #776971;
            /* Blue border to indicate selection */
            background-color: #e9e9e9;
            /* Light blue background for visibility */
        }



        /* --------------------- Page 4 ------------------------------ */
        .page-4 {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            margin-top: 20px;
        }

        .heading-btn-div {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 8px;
        }

        .rewrite-btn {
            background-color: var(--upload-button-color);
            border: none;
            height: 35px;
            width: 70px;
            border-radius: 12px;
            padding: 2px 10px;
        }

        .textarea-icons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .edit-controls {}

        .save-btn,
        .cancel-btn {
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
        }

        .save-btn {
            background-color: #4CAF50;

            color: white;
            border: none;
            border-radius: 5px;
        }

        .cancel-btn {
            background-color: lightgray;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .hidden {
            display: none;
        }

        #text-section p {
            box-sizing: border-box;
        }

        .icons-control-div {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .textArea-editable {
            box-sizing: border-box;
            padding: 15px;
            background-color: lightgray;
        }

        /* the animation of the copy btn to tick btn  */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s forwards;
        }

        .fade-out {
            animation: fadeOut 0.5s forwards;
        }

        .rewrite-reason-div {
            display: none;
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(94, 93, 93, 0.9);
            backdrop-filter: blur(0.5px);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.057);
            border-radius: 10px;
            box-sizing: border-box;
            top: 0;
            /* display: flex; */
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20%;


        }

        .rewrite-reason-div textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: none;
            height: 40px;
            resize: none;
        }

        .rewrite-reason-div div {
            margin-top: 10px;
            align-self: flex-end;
        }

        .rewrite-reason-div button {
            border: none;
            padding: 5px;
            border-radius: 3px;
        }
        
        .content{
        min-height: auto;
        }
        
        
        
        .paragraph-wrapper {
    position: relative;
    min-height: 50px; 
    /* Ensure the wrapper can contain absolutely positioned children */
      }

.loader-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8); /* Adjust the overlay background as needed */
    display: flex;
    justify-content: center;
    align-items: center;
}

.skeleton-loader {
    width: 100%;
    height: 100%;
    background-color: #ddd; 
    overflow: hidden;
    position: relative;
}

.skeleton-loader::before {
    content: "";
    position: absolute;
    top: 0;
    left: -150%;
    height: 100%;
    width: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: shimmer 2s infinite;
}



@keyframes shimmer {
    0% {
        left: -150%;
    }
    50% {
        left: 100%;
    }
    100% {
        left: 150%;
    }
}




@keyframes pulse {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

        /* Safari */
        @-webkit-keyframes spin {
            0% { -webkit-transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .response-navigation{
        display:flex;
        align-items:center;
        }
        
        .response-navigation button{
    border: none;
    background-color: transparent;
    display: flex;
    align-items: center;
    	padding: 0;
        
        }
        
        
        .textarea.content {
    width: 100%; /* Full-width */
    padding: 10px; /* Padding inside the textarea */
    margin: 5px 0; /* Margin for spacing around each textarea */
    box-sizing: border-box; /* Include padding in total width and height */
    border: 1px solid #ccc; /* Light grey border */
    border-radius: 4px; /* Rounded borders */
    background-color: #f8f8f8; /* Light grey background */
    resize: none; /* Disable resizing */
    font-family: Arial, sans-serif; /* Professional font */
    font-size: 14px; /* Readable font size */
    line-height: 1.5; /* Spacing between lines */
    height: auto;
}

/* Additional styling for the rewrite reason div for consistency */
.rewrite-reason-div textarea {
    width: calc(100% - 20px); /* Adjusting width considering padding */
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-top: 10px; /* Spacing above the textarea */
}
    </style>
    
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
</head>

<body>

    <header class="navbar">
     

        <nav class="nav-links">

            <a href="#how-it-works">How it works</a>


            <a onclick="writeNewCoverLetter()" class="button" role="button" aria-label="Create new cover letter">New cover
                letter</a>
        </nav>
    </header>
    <main>
        <!-- ----------------------------------------Page 1------------------------- -->
        <div class="onboarding-container">


            <section class="pagess" id="page1">
                <div class="heading-section">
                    <h1>Første skridt mod drømmejobbet</h1>
                    <div class="progress-bar">
                        <div class="progress"></div>
                    </div>
                    <p>Trin 1 ud af 3</p>
                </div>

                <h2>Upload dit CV og jobopslaget</h2>
                <p>Vores AI skriver naturlige og personlige ansøgninger, som åbner døren til jobsamtalen. Start med at uploade dit CV og jobopslaget.
                </p>


                <div class="upload-section">
                    <div class="upload-option">
                        <p>Upload dit CV som PDF-fil </p>
                        <div style="min-height: 38px;">

                            <button class="upload-button" id="cv-upload-btn"
                                onclick="document.getElementById(`cv-upload-input`).click()">
                                Upload CV

                            </button>
                            <span class="showFile" id="cv-show-file"
                                style="display: none; align-items: center; justify-content: space-between;">
                                <span>fileuploaded</span>


                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 72 72"
                                    style="margin-right: -6px;">
                                    <path fill="black"
                                        d="m58.14 21.78l-7.76-8.013l-14.29 14.22l-14.22-14.22l-8.013 8.013L28.217 36l-14.36 14.22l8.014 8.013l14.22-14.22l14.29 14.22l7.76-8.013L43.921 36z" />
                                    <path fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-miterlimit="10" stroke-width="2"
                                        d="m58.14 21.78l-7.76-8.013l-14.29 14.22l-14.22-14.22l-8.013 8.013L28.207 36l-14.35 14.22l8.014 8.013l14.22-14.22l14.29 14.22l7.76-8.013L43.921 36z" />
                                </svg>

                            </span>
                            <input type="file" class="file-upload-input" id="cv-upload-input" accept="application/pdf"
                                style="display: none;" onchange="showFile()" multiple=false>
                        </div>
                        <span class="Or-span">Eller</span>
                        <p style="text-align: left; font-weight: 500;">Indsæt teksten fra dit CV her                        </p>
                        <div class="textarea-with-button-container" id="cv-textarea-container">
                            <textarea id="cv-textarea"
                                placeholder="I am a software engineer with experience..."></textarea>
                            <button onclick="getLastCv();" id="last_cv_button" class="textarea-button">Insert Last CV</button>
                        </div>


                    </div>
                    <div class="upload-option">
                        <p>Upload jobopslaget som PDF-fil </p>
                        <div style="min-height: 38px;">

                            <button class="upload-button" id="jobpost-upload-btn"
                                onclick="document.getElementById(`jobpost-upload-input`).click()">
                                Upload jobopslaget
                            </button>
                            <span class="showFile" id="jobpost-show-file"
                                style="display: none; align-items: center; justify-content: space-between;">
                                <span>fileuploaded</span>


                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 72 72"
                                    style="margin-right: -6px;">
                                    <path fill="black"
                                        d="m58.14 21.78l-7.76-8.013l-14.29 14.22l-14.22-14.22l-8.013 8.013L28.217 36l-14.36 14.22l8.014 8.013l14.22-14.22l14.29 14.22l7.76-8.013L43.921 36z" />
                                    <path fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-miterlimit="10" stroke-width="2"
                                        d="m58.14 21.78l-7.76-8.013l-14.29 14.22l-14.22-14.22l-8.013 8.013L28.207 36l-14.35 14.22l8.014 8.013l14.22-14.22l14.29 14.22l7.76-8.013L43.921 36z" />
                                </svg>

                            </span>
                            <input type="file" class="file-upload-input" id="jobpost-upload-input"
                                accept="application/pdf" style="display: none;" onchange="showFileJobpost()"
                                multiple=false>
                        </div>
                        <span class="Or-span">Eller</span>
                        <p style="text-align: left; font-weight: 500;">Indsæt teksten fra jobopslaget her
                        </p>
                        <textarea id="jobpost-textarea"
                            placeholder="I am software engineer my exprience ..."></textarea>
                    </div>


                </div>


                <div class="next-btn-div">

                    <button onclick="getData()" id="first-page-check" class="next-button">Næste
                    </button>
                </div>
            </section>

            <!-- --------------------------------------- Page 2 ------------------------------------------  -->
            <section class="pagess" id="page2" style="display: none;">

                <div class="heading-section">
                    <h1>Gør din ansøgning personlig</h1>
                    <div class="progress-bar">
                        <div class="progress" style="padding-left: 20%;"></div>
                    </div>
                    <p>Trin 2 ud af 3</p>
                </div>
                <h2>Fortæl os om dig selv for en skræddersyet ansøgning</h2>
                <p class="get-started-p">Ved at fortælle os lidt mere om dig selv, hjælper du os med at skræddersy en ansøgning, der virkelig viser, hvem du er.</p>

                <div>
                    <div class="form-section">
                        <label for="position1">Hvad motiverer dig til at søge stillingen? </label>
                        <div class="textarea-container">
                            <textarea id="position1" placeholder=""></textarea>
                            <!-- Example SVG for the information icon -->
                            <svg class="info-icon" fill="#5c94dd" width="24" height="24"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path
                                    d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z" />
                            </svg>
                        </div>
                        <button onclick="generateMotivation()">
                        Giv mig ideer

                            <svg width="16" height="19" viewBox="0 0 7 9" fill="none" xmlns="http://www.w3.org/2000/svg"
                                style="margin-left: 5px;">
                                <path
                                    d="M2.18746 2.1L1.45829 2.625L1.86662 1.6875L1.45829 0.75L2.18746 1.275L2.91662 0.75L2.50829 1.6875L2.91662 2.625L2.18746 2.1ZM5.68746 5.775L6.41662 5.25L6.00829 6.1875L6.41662 7.125L5.68746 6.6L4.95829 7.125L5.36662 6.1875L4.95829 5.25L5.68746 5.775ZM6.41662 0.75L6.00829 1.6875L6.41662 2.625L5.68746 2.1L4.95829 2.625L5.36662 1.6875L4.95829 0.75L5.68746 1.275L6.41662 0.75ZM3.89079 4.7925L4.60246 3.8775L3.98412 3.0825L3.27246 3.9975L3.89079 4.7925ZM4.19121 2.73375L4.87371 3.61125C4.98746 3.75 4.98746 3.99375 4.87371 4.14L1.46996 8.51625C1.35621 8.6625 1.16662 8.6625 1.05871 8.51625L0.376206 7.63875C0.262456 7.5 0.262456 7.25625 0.376206 7.11L3.77996 2.73375C3.89371 2.5875 4.08329 2.5875 4.19121 2.73375Z"
                                    fill="white" />
                            </svg>
                        </button>

                    </div>


                    <div class="form-section">
                        <label for="position2">Er der projekter, præstationer eller tidligere jobs, du ønsker vi skal fremhæve særligt i din ansøgning?
                        </label>
                        <div class="textarea-container">
                            <textarea id="position2" placeholder=""></textarea>
                            <!-- Example SVG for the information icon -->
                            <svg class="info-icon" fill="#5c94dd" width="24" height="24"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path
                                    d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z" />
                            </svg>
                        </div>
                        <button onclick="generateExperience()">
                        Giv mig ideer
                            <svg width="16" height="19" viewBox="0 0 7 9" fill="none" xmlns="http://www.w3.org/2000/svg"
                                style="margin-left: 5px;">
                                <path
                                    d="M2.18746 2.1L1.45829 2.625L1.86662 1.6875L1.45829 0.75L2.18746 1.275L2.91662 0.75L2.50829 1.6875L2.91662 2.625L2.18746 2.1ZM5.68746 5.775L6.41662 5.25L6.00829 6.1875L6.41662 7.125L5.68746 6.6L4.95829 7.125L5.36662 6.1875L4.95829 5.25L5.68746 5.775ZM6.41662 0.75L6.00829 1.6875L6.41662 2.625L5.68746 2.1L4.95829 2.625L5.36662 1.6875L4.95829 0.75L5.68746 1.275L6.41662 0.75ZM3.89079 4.7925L4.60246 3.8775L3.98412 3.0825L3.27246 3.9975L3.89079 4.7925ZM4.19121 2.73375L4.87371 3.61125C4.98746 3.75 4.98746 3.99375 4.87371 4.14L1.46996 8.51625C1.35621 8.6625 1.16662 8.6625 1.05871 8.51625L0.376206 7.63875C0.262456 7.5 0.262456 7.25625 0.376206 7.11L3.77996 2.73375C3.89371 2.5875 4.08329 2.5875 4.19121 2.73375Z"
                                    fill="white" />
                            </svg>
                        </button>
                    </div>


                    <div class="form-section">
                        <label for="position3">Hvad ved din personlighed passer godt til dette job?</label>
                        <div class="textarea-container">
                            <textarea id="position3" placeholder=""></textarea>
                            <!-- Example SVG for the information icon -->
                            <svg class="info-icon" fill="#5c94dd" width="24" height="24"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path
                                    d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z" />
                            </svg>
                        </div>
                        <button onclick="generatePersonality()">
                        Giv mig ideer
                            <svg width="16" height="19" viewBox="0 0 7 9" fill="none" xmlns="http://www.w3.org/2000/svg"
                                style="margin-left: 5px;">
                                <path
                                    d="M2.18746 2.1L1.45829 2.625L1.86662 1.6875L1.45829 0.75L2.18746 1.275L2.91662 0.75L2.50829 1.6875L2.91662 2.625L2.18746 2.1ZM5.68746 5.775L6.41662 5.25L6.00829 6.1875L6.41662 7.125L5.68746 6.6L4.95829 7.125L5.36662 6.1875L4.95829 5.25L5.68746 5.775ZM6.41662 0.75L6.00829 1.6875L6.41662 2.625L5.68746 2.1L4.95829 2.625L5.36662 1.6875L4.95829 0.75L5.68746 1.275L6.41662 0.75ZM3.89079 4.7925L4.60246 3.8775L3.98412 3.0825L3.27246 3.9975L3.89079 4.7925ZM4.19121 2.73375L4.87371 3.61125C4.98746 3.75 4.98746 3.99375 4.87371 4.14L1.46996 8.51625C1.35621 8.6625 1.16662 8.6625 1.05871 8.51625L0.376206 7.63875C0.262456 7.5 0.262456 7.25625 0.376206 7.11L3.77996 2.73375C3.89371 2.5875 4.08329 2.5875 4.19121 2.73375Z"
                                    fill="white" />
                            </svg>
                        </button>
                    </div>

                    <div class="navigation-buttons">
                        <div>
                            <button class="back-button">
                            Tilbage
                            </button>
                        </div>

                        <div>
                            <button onclick="skip_page_2()" class="skip-button">Skip for now</button>
                            <button onclick="getpage2data()" class="next-button">Næste
                            </button>
                        </div>
                    </div>
                </div>

            </section>

            <!-- Page 3 -->
            <section class="pagess" id="page3" style="display: none;">

                <div id="lightbox" style="display:none;">
                    <span id="lightboxClose" style="cursor:pointer;">&times; Luk</span>
                    <img src="#" alt="Preview">
                </div>



                <div class="heading-section">
                    <h1>Vælg opsætning</h1>
                    <div class="progress-bar">
                        <div class="progress" style="padding-left: 77%;"></div>
                    </div>
                    <p>Trin 3 ud af 3
                    </p>
                </div>
                <h2>Vælg hvordan din ansøgning skal se ud
                </h2>
                <p class="get-started-p">Et godt førsteindtryk er afgørende. Vælg hvordan vi skal opsætte din ansøgning, så den er skræddersyet til dig.
                </p>
                <div class="template">
                    <div class="content">
                        
                        <h2> Den personlige ansøgning
                        </h2>

                        <p>Vi udvider afsnittene om din motivation og personlighed, så arbejdsgiveren kan se, hvorfor du er det perfekte match.
                        </p>
                        <button onclick="selectTemplate(this)">Vælg denne opsætning</button>

                    </div>

                    <div class="image">
                        <img src="https://myre-bekaempelse.dk/wp-content/plugins/security-api-caller/assets/page3/card3placeholder.jpg" alt="Marketing Manager">
                        <!-- View button added below the image -->
                        <!-- <button class="view-button" data-target="image1">View</button> -->
                    </div>
                </div>

                <div class="template">
                    <div class="content">
                        
                        <h2> Den klassike ansøgning
                        </h2>

                        <p>Enkel og ligetil. Din ansøgning bliver skrevet flydende uden overskrifter, hvilket giver et rent og professionelt look.
                        </p>
                        <button onclick="selectTemplate(this)">Vælg denne opsætning</button>

                    </div>

                    <div class="image">
                        <img src="https://myre-bekaempelse.dk/wp-content/plugins/security-api-caller/assets/page3/card2placeholder.jpg" alt="Marketing Manager">
                        <!-- View button added below the image -->
                        <!-- <button class="view-button" data-target="image1">View</button> -->
                    </div>
                </div>

                <div class="template">
                    <div class="content">
                        
                        <h2> Den moderne ansøgning

                        </h2>

                        <p>Let at læse og følge med i. Vi bruger overskrifter til at dele din ansøgning op, så dine vigtigste punkter springer i øjnene

                        </p>
                        <button onclick="selectTemplate(this)">Vælg denne opsætning</button>

                    </div>

                    <div class="image">
                        <img src="https://myre-bekaempelse.dk/wp-content/plugins/security-api-caller/assets/page3/card1placeholder.jpg" alt="Marketing Manager">
                        <!-- View button added below the image -->
                        <!-- <button class="view-button" data-target="image1">View</button> -->
                    </div>
                </div>

                <div class="navigation-buttons">
                    <button class="back-button">
                    Tilbage
                    </button>

                    <div>
                        <button onclick="initiateAPICalls()" disabled id="write-application"
                            style="background-image: none; background-color: white; color: black; border: 1px solid grey;"
                            class="next-button"> <span> Skriv ansøgningen </span> <svg
                                xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                                <g fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M21.264 2.293a1 1 0 0 0-1.415 0l-.872.872a3.001 3.001 0 0 0-3.415.587L4.955 14.358l5.657 5.657L21.22 9.41a3 3 0 0 0 .586-3.415l.873-.873a1 1 0 0 0 0-1.414zm-4.268 8.51l-6.384 6.384l-2.828-2.829l6.383-6.383zm1.818-1.818l.99-.99a1 1 0 0 0 0-1.415l-1.413-1.414a1 1 0 0 0-1.415 0l-.99.99z"
                                        clip-rule="evenodd" />
                                    <path d="m2 22.95l2.122-7.778l5.656 5.657z" />
                                </g>
                            </svg></button>
                    </div>
                </div>
            </section>

            <!------------------------------------- Page-4------------------------------------------------------>
            <section class="page-4 pagess" style="display: none;">

                
                <div class="editable-section">

                    <div class="heading-btn-div">
                        
                        <button class="rewrite-btn">Rewrite </button>
                    </div>
                    <div id="text-section" contenteditable="false">
                        <div style="position: relative;">
                            <textarea class="content" contenteditable="false">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Provident, nemo
                                velit! Ipsum
                                repellendus
                                obcaecati sapiente cupiditate reiciendis alias quibusdam ullam, quis aliquam neque
                                itaque
                                corporis,
                                dolorem deleniti. Veniam harum ex exercitationem. Voluptatibus quod, dolorem deleniti
                                consequatur
                                nostrum magnam sint. Exercitationem cupiditate similique natus eaque consequuntur
                                laborum,
                                ipsam
                                inventore explicabo! Sint suscipit dolorum, iusto aliquid tempore quis. Dolorum aliquid,
                                fugit
                                tempore nisi beatae autem, fugiat impedit est velit quod consequuntur ea nobis! At
                                quibusdam
                                voluptatum beatae, perferendis ad corporis explicabo enim odio illum fugiat alias vel
                                quo
                                est
                                dolores ea itaque nostrum! Odit nihil delectus ipsa iste expedita perspiciatis quam
                                natus!
                            </textarea>
                            <div class="rewrite-reason-div">
                                <textarea placeholder="Tell reason to rewrite or skip"></textarea>
                                <div>
                                    <button class="btn-skip">Skip</button>
                                    <button class="btn-rewrite">Rewrite</button>
                                </div>
                            </div>

                        </div>
                        <div class="icons-control-div">

                            <div class="textarea-icons">
                            	<div class="response-navigation">
                                <button class="arrow-left nav-btn next-btn" aria-label="Next">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16">
                                      <path fill="currentColor" d="M12 13h-2L5 8l5-5h2L7 8z" />
                                  </svg>
                              </button>
                               
                                <div class="response-backward">

                                    <span class="response-counter"> 1/1 </span>
                                </div>
                                
                                <button class="arrow-right nav-btn prev-btn" aria-label="Previous">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" style="transform:rotate(180deg)">
                                      <path fill="currentColor" d="M12 13h-2L5 8l5-5h2L7 8z" />
                                  </svg>
                              </button>
                               
                              
                              </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="1.5"
                                        d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    class="copy-btn">
                                    <path fill="currentColor"
                                        d="M19 19H8q-.825 0-1.412-.587T6 17V3q0-.825.588-1.412T8 1h7l6 6v10q0 .825-.587 1.413T19 19M14 8V3H8v14h11V8zM4 23q-.825 0-1.412-.587T2 21V7h2v14h11v2zM8 3v5zv14z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16"
                                    class="tick-btn" style="display: none;">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="1.5" d="m2.75 8.75l3.5 3.5l7-7.5" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    class="edit-btn">
                                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2">
                                        <path
                                            d="m16.475 5.408l2.117 2.117m-.756-3.982L12.109 9.27a2.118 2.118 0 0 0-.58 1.082L11 13l2.648-.53c.41-.082.786-.283 1.082-.579l5.727-5.727a1.853 1.853 0 1 0-2.621-2.621" />
                                        <path d="M19 15v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3" />
                                    </g>
                                </svg>
                            </div>
                            <div class="edit-controls hidden">
                                <button class="save-btn">Save</button>
                                <button class="cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="editable-section">

                    <div class="heading-btn-div">
                        
                        <button class="rewrite-btn">Rewrite </button>
                    </div>
                    <div id="text-section" contenteditable="false">
                        <div style="position: relative;">
                            <textarea class="content" contenteditable="false">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Provident, nemo
                                velit! Ipsum
                                repellendus
                                obcaecati sapiente cupiditate reiciendis alias quibusdam ullam, quis aliquam neque
                                itaque
                                corporis,
                                dolorem deleniti. Veniam harum ex exercitationem. Voluptatibus quod, dolorem deleniti
                                consequatur
                                nostrum magnam sint. Exercitationem cupiditate similique natus eaque consequuntur
                                laborum,
                                ipsam
                                inventore explicabo! Sint suscipit dolorum, iusto aliquid tempore quis. Dolorum aliquid,
                                fugit
                                tempore nisi beatae autem, fugiat impedit est velit quod consequuntur ea nobis! At
                                quibusdam
                                voluptatum beatae, perferendis ad corporis explicabo enim odio illum fugiat alias vel
                                quo
                                est
                                dolores ea itaque nostrum! Odit nihil delectus ipsa iste expedita perspiciatis quam
                                natus!
                            </textarea>
                            <div class="rewrite-reason-div">
                                <textarea placeholder="Tell reason to rewrite or skip"></textarea>
                                <div>
                                    <button class="btn-skip">Skip</button>
                                    <button class="btn-rewrite">Rewrite</button>
                                </div>
                            </div>

                        </div>
                        <div class="icons-control-div">

                            <div class="textarea-icons">
                               <div class="response-navigation">
                                <button class="arrow-left nav-btn next-btn" aria-label="Next">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16">
                                      <path fill="currentColor" d="M12 13h-2L5 8l5-5h2L7 8z" />
                                  </svg>
                              </button>
                               
                                <div class="response-backward">

                                    <span class="response-counter"> 1/1 </span>
                                </div>
                                
                                <button class="arrow-right nav-btn prev-btn" aria-label="Previous">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" style="transform:rotate(180deg)">
                                      <path fill="currentColor" d="M12 13h-2L5 8l5-5h2L7 8z" />
                                  </svg>
                              </button>
                               
                              
                              </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="1.5"
                                        d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    class="copy-btn">
                                    <path fill="currentColor"
                                        d="M19 19H8q-.825 0-1.412-.587T6 17V3q0-.825.588-1.412T8 1h7l6 6v10q0 .825-.587 1.413T19 19M14 8V3H8v14h11V8zM4 23q-.825 0-1.412-.587T2 21V7h2v14h11v2zM8 3v5zv14z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16"
                                    class="tick-btn" style="display: none;">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="1.5" d="m2.75 8.75l3.5 3.5l7-7.5" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    class="edit-btn">
                                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2">
                                        <path
                                            d="m16.475 5.408l2.117 2.117m-.756-3.982L12.109 9.27a2.118 2.118 0 0 0-.58 1.082L11 13l2.648-.53c.41-.082.786-.283 1.082-.579l5.727-5.727a1.853 1.853 0 1 0-2.621-2.621" />
                                        <path d="M19 15v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3" />
                                    </g>
                                </svg>
                            </div>
                            <div class="edit-controls hidden">
                                <button class="save-btn">Save</button>
                                <button class="cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="editable-section">

                    <div class="heading-btn-div">
                        
                        <button class="rewrite-btn">Rewrite </button>
                    </div>
                    <div id="text-section" contenteditable="false">
                        <div style="position: relative;">
                            <textarea  class="content" contenteditable="false">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Provident, nemo
                                velit! Ipsum
                                repellendus
                                obcaecati sapiente cupiditate reiciendis alias quibusdam ullam, quis aliquam neque
                                itaque
                                corporis,
                                dolorem deleniti. Veniam harum ex exercitationem. Voluptatibus quod, dolorem deleniti
                                consequatur
                                nostrum magnam sint. Exercitationem cupiditate similique natus eaque consequuntur
                                laborum,
                                ipsam
                                inventore explicabo! Sint suscipit dolorum, iusto aliquid tempore quis. Dolorum aliquid,
                                fugit
                                tempore nisi beatae autem, fugiat impedit est velit quod consequuntur ea nobis! At
                                quibusdam
                                voluptatum beatae, perferendis ad corporis explicabo enim odio illum fugiat alias vel
                                quo
                                est
                                dolores ea itaque nostrum! Odit nihil delectus ipsa iste expedita perspiciatis quam
                                natus!
                            </textarea>
                            <div class="rewrite-reason-div">
                                <textarea placeholder="Tell reason to rewrite or skip"></textarea>
                                <div>
                                    <button class="btn-skip">Skip</button>
                                    <button class="btn-rewrite">Rewrite</button>
                                </div>
                            </div>

                        </div>
                        <div class="icons-control-div">

                            <div class="textarea-icons">
                                <div class="response-navigation">
                                <button class="arrow-left nav-btn next-btn" aria-label="Next">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16">
                                      <path fill="currentColor" d="M12 13h-2L5 8l5-5h2L7 8z" />
                                  </svg>
                              </button>
                               
                                <div class="response-backward">

                                    <span class="response-counter"> 1/1 </span>
                                </div>
                                
                                <button class="arrow-right nav-btn prev-btn" aria-label="Previous">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" style="transform:rotate(180deg)">
                                      <path fill="currentColor" d="M12 13h-2L5 8l5-5h2L7 8z" />
                                  </svg>
                              </button>
                               
                              
                              </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="1.5"
                                        d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    class="copy-btn">
                                    <path fill="currentColor"
                                        d="M19 19H8q-.825 0-1.412-.587T6 17V3q0-.825.588-1.412T8 1h7l6 6v10q0 .825-.587 1.413T19 19M14 8V3H8v14h11V8zM4 23q-.825 0-1.412-.587T2 21V7h2v14h11v2zM8 3v5zv14z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16"
                                    class="tick-btn" style="display: none;">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="1.5" d="m2.75 8.75l3.5 3.5l7-7.5" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    class="edit-btn">
                                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2">
                                        <path
                                            d="m16.475 5.408l2.117 2.117m-.756-3.982L12.109 9.27a2.118 2.118 0 0 0-.58 1.082L11 13l2.648-.53c.41-.082.786-.283 1.082-.579l5.727-5.727a1.853 1.853 0 1 0-2.621-2.621" />
                                        <path d="M19 15v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3" />
                                    </g>
                                </svg>
                            </div>
                            <div class="edit-controls hidden">
                                <button class="save-btn">Save</button>
                                <button class="cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>




                <div class="editable-section" id="last_div">

                    <div class="heading-btn-div">
                        
                        <button class="rewrite-btn">Rewrite </button>
                    </div>
                    <div id="text-section" contenteditable="false">
                        <div style="position: relative;">
                            <textarea class="content" contenteditable="false">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Provident, nemo
                                velit! Ipsum
                                repellendus
                                obcaecati sapiente cupiditate reiciendis alias quibusdam ullam, quis aliquam neque
                                itaque
                                corporis,
                                dolorem deleniti. Veniam harum ex exercitationem. Voluptatibus quod, dolorem deleniti
                                consequatur
                                nostrum magnam sint. Exercitationem cupiditate similique natus eaque consequuntur
                                laborum,
                                ipsam
                                inventore explicabo! Sint suscipit dolorum, iusto aliquid tempore quis. Dolorum aliquid,
                                fugit
                                tempore nisi beatae autem, fugiat impedit est velit quod consequuntur ea nobis! At
                                quibusdam
                                voluptatum beatae, perferendis ad corporis explicabo enim odio illum fugiat alias vel
                                quo
                                est
                                dolores ea itaque nostrum! Odit nihil delectus ipsa iste expedita perspiciatis quam
                                natus!
                            </textarea>
                            <div class="rewrite-reason-div">
                                <textarea placeholder="Tell reason to rewrite or skip"></textarea>
                                <div>
                                    <button class="btn-skip">Skip</button>
                                    <button class="btn-rewrite">Rewrite</button>
                                </div>
                            </div>

                        </div>
                        <div class="icons-control-div">

                            <div class="textarea-icons">
                                <div class="response-navigation">
                                <button class="arrow-left nav-btn next-btn" aria-label="Next">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16">
                                      <path fill="currentColor" d="M12 13h-2L5 8l5-5h2L7 8z" />
                                  </svg>
                              </button>
                               
                                <div class="response-backward">

                                    <span class="response-counter"> 1/1 </span>
                                </div>
                                
                                <button class="arrow-right nav-btn prev-btn" aria-label="Previous">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" style="transform:rotate(180deg)">
                                      <path fill="currentColor" d="M12 13h-2L5 8l5-5h2L7 8z" />
                                  </svg>
                              </button>
                               
                              
                              </div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="1.5"
                                        d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    class="copy-btn">
                                    <path fill="currentColor"
                                        d="M19 19H8q-.825 0-1.412-.587T6 17V3q0-.825.588-1.412T8 1h7l6 6v10q0 .825-.587 1.413T19 19M14 8V3H8v14h11V8zM4 23q-.825 0-1.412-.587T2 21V7h2v14h11v2zM8 3v5zv14z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16"
                                    class="tick-btn" style="display: none;">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="1.5" d="m2.75 8.75l3.5 3.5l7-7.5" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    class="edit-btn">
                                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2">
                                        <path
                                            d="m16.475 5.408l2.117 2.117m-.756-3.982L12.109 9.27a2.118 2.118 0 0 0-.58 1.082L11 13l2.648-.53c.41-.082.786-.283 1.082-.579l5.727-5.727a1.853 1.853 0 1 0-2.621-2.621" />
                                        <path d="M19 15v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3" />
                                    </g>
                                </svg>
                            </div>
                            <div class="edit-controls hidden">
                                <button class="save-btn">Save</button>
                                <button class="cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="back-button">
                Tilbage
                 </button>



            </section>
        </div>
    </main>
    
    <script>
    	
        function writeNewCoverLetter() {
    localStorage.setItem("cv_data", "");
    localStorage.setItem("jobpost_data", "");
    localStorage.setItem("job_data", "");

    

var fileInputs = document.querySelectorAll("input");
fileInputs.forEach(function(input) {
    // Attempt to clear the input directly for older browsers or non-strict modes
    try {
    input.disabled = false;
        input.value = ""; 
    } catch (e) {
        // For security reasons, some browsers may not allow clearing of file input this way
        console.error("Error clearing file input:", e);
    }
    
    // If the input is not cleared, replace it with a clone
    if (input.value) {
        var clone = input.cloneNode(true);
        input.parentNode.replaceChild(clone, input);
    }
});

}
    	
    </script>
    <! ----------------------- Page 1 backend -------------------------------- >

    <script>
   	function formDataToObject(formData) {
    console.log("Helllllllllllllllllllllllllllllll");
    const object = {};
    formData.forEach((value, key) => {
        // Check if property already exists to handle multiple values per key
        if (object.hasOwnProperty(key)) {
            
            if (!Array.isArray(object[key])) {
                object[key] = [object[key]];
            }
            object[key].push(value);
        } else {
            object[key] = value;
        }
    });
    return object;
}

    function getLastCv(){
    const lastCv = localStorage.getItem("last_cv");
    if (lastCv) {
    console.log(JSON.parse(lastCv));
        document.getElementById("cv-textarea").value = JSON.parse(lastCv).CV;
        document.getElementById("last_cv_button").style.display = "none";	
    }
}

document.addEventListener("DOMContentLoaded", function() {
    console.log("hello");
    document.getElementById("last_cv_button").style.display = "none";	
    getSingleUserCV();
});

function getSingleUserCV() {
    fetch(sac_ajax_object.ajax_url, { // Use sac_ajax_object.ajax_url
        method: "POST",
        credentials: "same-origin",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            "action": "sac_handle_get_single_user_cv",
            // Assuming sac_ajax_object.security exists and is valid
            // "security": sac_ajax_object.security // Use sac_ajax_object.security
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Network response was not ok");
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log("CV:", data.data.cv);
            localStorage.setItem("last_cv", JSON.stringify(data.data.cv));
            document.getElementById("last_cv_button").style.display = "block";
            // Optional: Update CV display or other actions here
            // document.getElementById("cvDisplayArea").innerHTML = data.data.cv.CV;
        } else {
            console.error("Error:", data.data.message);
            document.getElementById("last_cv_button").style.display = "none";
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
    });
}


    //Function to hide the info button in the text areas:
    
    
    document.addEventListener("DOMContentLoaded", function () {
        // Find all textareas and iterate over them
        document.querySelectorAll(".form-section textarea").forEach(function (textarea) {
            // Function to toggle the icon visibility
            

            // Initial check on page load
            

            // Add event listeners for input changes
            
        });
    });
    // Function to enable disable next button based on information
    document.addEventListener("DOMContentLoaded", function () {
        // Input and textarea elements
        const cvUploadInput = document.getElementById("cv-upload-input");
        const jobPostUploadInput = document.getElementById("jobpost-upload-input");
        const cvTextarea = document.getElementById("cv-textarea");
        const jobPostTextarea = document.getElementById("jobpost-textarea");

        // The Next button element
        const nextButton = document.getElementById("first-page-check");

        const textAreaButton = document.querySelector(".textarea-button");
        // Function to update the Next button state
        function updateNextButtonState() {

            //Disabling and Enabling based on tex area written

            if (cvTextarea.value.trim().length > 0 || cvUploadInput.files.length > 0) {
                document.getElementById("cv-upload-btn").disabled = true;
                textAreaButton.style.display = "none";

            } else {

                document.getElementById("cv-upload-btn").disabled = false;
                textAreaButton.style.display = "block";
            }

            if (jobPostTextarea.value.trim().length > 0) {
                document.getElementById("jobpost-upload-btn").disabled = true;
            } else {
                document.getElementById("jobpost-upload-btn").disabled = false;
            }

            // Check if the CV and job post are uploaded or written
            const isCvUploadedOrWritten = cvUploadInput.files.length > 0 || cvTextarea.value.trim().length > 0;
            const isJobPostUploadedOrWritten = jobPostUploadInput.files.length > 0 || jobPostTextarea.value.trim().length > 0;

            // Enable or disable the Next button based on conditions
            nextButton.disabled = !(isCvUploadedOrWritten && isJobPostUploadedOrWritten);
            if (nextButton.disabled) {
                nextButton.style.backgroundImage = "none";
                nextButton.style.backgroundColor = "white";
                nextButton.style.color = "grey";
                nextButton.style.border = "1px solid grey";
            } else {
                nextButton.style.color = "white";
                nextButton.style.backgroundColor = "#007bff";
                nextButton.style.backgroundImage = "var(--next-button-color)";
            }
        }

        // Attach event listeners
        cvUploadInput.addEventListener("change", updateNextButtonState);
        jobPostUploadInput.addEventListener("change", updateNextButtonState);
        cvTextarea.addEventListener("input", updateNextButtonState); // "input" event for textarea
        jobPostTextarea.addEventListener("input", updateNextButtonState); // "input" event for textarea

        // Initial check in case of any pre-filled values (if applicable)
        updateNextButtonState();
    });
    
    // Function to get the data.
        function getData() {
        
            var cv_data = "";
            if (document.getElementById("cv-upload-input").files[0] != undefined) {
                var cv = document.getElementById("cv-upload-input").files[0];

                // Usage example
                parsePDF(cv).then((data) => {

                    cv_data = data;
                    localStorage.setItem("cv_data", "This is CV:"+cv_data);
                    console.log("Parsed PDF data:", data);
                    saveCvToDatabase();
                    // Use the parsed data as needed
                }).catch((error) => {
                    console.error("Failed to parse PDF:", error);
                });


            } else {
                cv_data = document.getElementById("cv-textarea").value;
                localStorage.setItem("cv_data", cv_data);
                saveCvToDatabase();
            }

            var jobpost_data = "";
            if (document.getElementById("jobpost-upload-input").files[0] != undefined) {
                var jobpost = document.getElementById("jobpost-upload-input").files[0];

                // Usage example
                parsePDF(jobpost).then((data) => {
                    jobpost_data = data;
                    localStorage.setItem("jobpost_data",  "This is job post:" + jobpost_data);
                    console.log("Parsed PDF data:", data);
                    // Use the parsed data as needed
                }).catch((error) => {
                    console.error("Failed to parse PDF:", error);
                });

            } else {
                jobpost_data = document.getElementById("jobpost-textarea").value;
                localStorage.setItem("jobpost_data", jobpost_data);
            }

            
            
            
        }

        // Function to parse and read data from a PDF file
        function parsePDF(file) {
            return new Promise((resolve, reject) => {
                // Load the PDF file
                const reader = new FileReader();
                reader.onload = function () {
                    const typedArray = new Uint8Array(this.result);
                    // Load the PDF document
                    pdfjsLib.getDocument(typedArray).promise.then((pdf) => {
                        // Read the text content of each page
                        const numPages = pdf.numPages;
                        const pages = [];
                        for (let i = 1; i <= numPages; i++) {
                            pdf.getPage(i).then((page) => {
                                page.getTextContent().then((textContent) => {
                                    const pageText = textContent.items.map((item) => item.str).join(" ");
                                    pages.push(pageText);
                                    if (pages.length === numPages) {
                                        resolve(pages.join(" "));
                                    }
                                });
                            });
                        }
                    }).catch((error) => {
                        reject(error);
                    });
                };
                reader.onerror = function (error) {
                    reject(error);
                };
                reader.readAsArrayBuffer(file);
            });
        }



    // -------------------------------------- PAGE HANDLER -------------------------------------

    let currentPageIndex = 0; // Start with the first page
    function showPage(index) {
        const pages = document.querySelectorAll(".pagess");
        if (index < 0 || index >= pages.length) return; // Guard clause for invalid indexes

        // Hide all pages
        pages.forEach(page => {
            page.style.display = "none";
        });

        // Show the target page
        pages[index].style.display = "block";
        currentPageIndex = index; // Update the current page index
    }

    const nextButtons = document.querySelectorAll(".next-button");
    nextButtons.forEach(button => {
        button.addEventListener("click", function () {
            showPage(currentPageIndex + 1); // Show next page
        });
    });


    // Optional: If you have a back button
    const backButtons = document.querySelectorAll(".back-button");
    backButtons.forEach(button => {
        button.addEventListener("click", function () {
            showPage(currentPageIndex - 1); // Show previous page
        });
    });


    // ---------------------------- ENABLE FIRST PAGE BUTTON ------------------------------

    function enableFirstPage() {
        const firstpagebutton = document.getElementById("first-page-check");

        const jobpostInput = document.getElementById("jobpost-upload-input");
        const jobTextArea = document.getElementById("jobpost-textarea");

        if (jobpostInput.files[0] != undefined || jobTextArea.value != "") {
            firstpagebutton.disabled = false;
        } else {
            firstpagebutton.disabled = true;
            firstpagebutton.style.backgroundColor = "white";
            firstpagebutton.style.color = "grey";


        }

        const cvInput = document.getElementById("cv-upload-input");
        const cvTextArea = document.getElementById("cv-textarea");
        if (cvInput.files[0] != undefined || cvTextArea.value != "") {
            firstpagebutton.disabled = false;
        } else {
            firstpagebutton.disabled = true;
        }
    }
    //----------------------------------------------------- upload file CV ---------------------------------
    function showFile() {
        const cvUploadBtn = document.getElementById("cv-upload-btn");
        const cvInput = document.getElementById("cv-upload-input");
        const cvShowFile = document.getElementById("cv-show-file");
        const filenameShower = cvShowFile.querySelector("span");
        const cvTextarea = document.getElementById("cv-textarea");


        if (cvInput.files[0] != undefined) {

            cvUploadBtn.style.display = "none";
            document.getElementById("last_cv_button").style.display = "none";
        }

        else {
            document.getElementById("last_cv_button").style.display = "block";
        }
        const filename = cvInput.files[0].name;
        filenameShower.textContent = filename
        cvShowFile.style.display = "flex"

        cvTextarea.disabled = true;

    }
    const deleteFileBtn = document.getElementById("cv-show-file").querySelector("svg");
    deleteFileBtn.addEventListener("click", () => {
        const cvUploadBtn = document.getElementById("cv-upload-btn");
        const cvInput = document.getElementById("cv-upload-input");
        const cvShowFile = document.getElementById("cv-show-file");
        const cvTextarea = document.getElementById("cv-textarea");

        cvShowFile.style.display = "none";
        cvUploadBtn.style.display = "block";
        cvUploadBtn.disabled=false;
        cvTextarea.disabled = false;
        document.getElementById("last_cv_button").style.display = "block";
    })

    //----------------------------------------------------- upload file Job post ----------------------------------------
    function showFileJobpost() {
        const jobpostUploadBtn = document.getElementById("jobpost-upload-btn");
        const jobpostInput = document.getElementById("jobpost-upload-input");
        const jobpostShowFile = document.getElementById("jobpost-show-file");
        const filenameShower = jobpostShowFile.querySelector("span");
        const jobpostTextarea = document.getElementById("jobpost-textarea");
        if (jobpostInput.files[0].name) {

            jobpostUploadBtn.style.display = "none";
        }
        const filename = jobpostInput.files[0].name;
        filenameShower.textContent = filename
        jobpostShowFile.style.display = "flex"

        jobpostTextarea.disabled = true;

    }
    const deleteJobFileBtn = document.getElementById("jobpost-show-file").querySelector("svg");
    deleteJobFileBtn.addEventListener("click", () => {
        const jobpostUploadBtn = document.getElementById("jobpost-upload-btn");
        const jobpostInput = document.getElementById("jobpost-upload-input");
        const jobpostShowFile = document.getElementById("jobpost-show-file");
        const jobpostTextarea = document.getElementById("jobpost-textarea");

        jobpostShowFile.style.display = "none";
        jobpostUploadBtn.style.display = "block"
        jobpostTextarea.disabled = false;
    })
	
    // --------------------------------Page 2 Backend ------------------------------------
    
    function skip_page_2(){
    	showPage(2);
        localStorage.setItem("motivation", "");
        localStorage.setItem("experience", "");
        localStorage.setItem("personality", "");
        
        console.log(localStorage.getItem("motivation"));
        console.log(localStorage.getItem("experience"));
        console.log(localStorage.getItem("personality"));
    }
    
    
    // ---------------------------- Lightbox ------------------------------
    document.addEventListener("DOMContentLoaded", function () {
        const images = document.querySelectorAll(".image img");
        let hoverTimeout;

        images.forEach((image, index) => {
            image.addEventListener("mouseenter", function () {
                // Clear any existing timeouts to prevent unwanted lightbox display
                clearTimeout(hoverTimeout);

                // Set a delay before showing the lightbox
                hoverTimeout = setTimeout(() => {
                    const lightbox = document.getElementById("lightbox");
                    lightbox.style.display = "flex";
                    lightbox.querySelector("img").src = "https://myre-bekaempelse.dk/wp-content/plugins/security-api-caller/assets/page3/card" + (index + 1) + "popup.jpg";
                    this.style.zIndex = 2;
                    this.style.position = "relative";
                }, 500); // 500ms delay before showing the lightbox
            });

            image.addEventListener("mouseleave", function () {
                // Cancel the timeout if the user leaves the image before the delay passes
                clearTimeout(hoverTimeout);
                this.style.zIndex = 0;
                this.style.position = "block";
            });
        });

        // Close the lightbox when clicking on the close button or outside the image
        document.getElementById("lightboxClose").addEventListener("click", function () {
            document.getElementById("lightbox").style.display = "none";
        });

        document.getElementById("lightbox").addEventListener("click", function (e) {
            // Close if the user clicks outside the image (e.g., on the lightbox background)
            if (e.target === this) {
                this.style.display = "none";
            }
        });
    });


    // ---------------------------- Page 3 Backend ------------------------------

 function selectTemplate(clickedButton) {
    // Enable and style the "Write Application" button
    const writeButton = document.getElementById("write-application");
    writeButton.disabled = false;
    writeButton.style.backgroundImage = "var(--next-button-color)";
    writeButton.style.color = "white";

    
    document.querySelectorAll(".template").forEach(function (template) {
        template.classList.remove("selected-template");
    });

    
    var parentTemplate = clickedButton.closest(".template");
    parentTemplate.classList.add("selected-template");

    // Store the index (or any identifier) of the selected template in localStorage
    var templateIndex = Array.from(document.querySelectorAll(".template")).indexOf(parentTemplate);
    localStorage.setItem("selectedTemplateIndex", templateIndex);
    if (templateIndex != 0){
    	console.log("hiding");
    	document.getElementById("last_div").style.display = "none";
    }
    else {
		document.getElementById("last_div").style.display = "block";
	}

    // Optionally, if you want to store more details about the selected template in localStorage
    var templateDetails = {
        index: templateIndex,
        title: parentTemplate.querySelector("h2").innerText,
        description: parentTemplate.querySelector("p").innerText,
        imageUrl: parentTemplate.querySelector("img").src
    };
    localStorage.setItem("selectedTemplateDetails", JSON.stringify(templateDetails));

    // Any additional logic you want to execute after selecting a template
}


function getpage2data() {
          // Retrieve the values from each textarea
          const motivation = document.getElementById("position1").value;
          const experience = document.getElementById("position2").value;
          const personality = document.getElementById("position3").value;

          // Store the values in localStorage
          localStorage.setItem("motivation", "motivation " + motivation);
          localStorage.setItem("experience", experience);
          localStorage.setItem("personality", personality);

          // Optionally, log the stored values to confirm or for debugging
          console.log("Data stored in localStorage:");
          console.log("Motivation:", motivation);
          console.log("Experience:", experience);
          console.log("Personality:", personality);

          // Here you can add any navigation or UI update logic if needed
      }


    // ---------------------------- Page 4 Backend ------------------------------
	


    const textSections = document.querySelectorAll("textarea.content");
    const editBtns = document.querySelectorAll(".edit-btn");
    const saveBtns = document.querySelectorAll(".save-btn");
    const cancelBtns = document.querySelectorAll(".cancel-btn");
    const editControls = document.querySelectorAll(".edit-controls");

    // Assuming each group of controls and textSections are aligned by their index
    textSections.forEach((textSection, index) => {
        let originalText = textSection.innerHTML; // Store original text for each text section

        const editBtn = editBtns[index];
        const saveBtn = saveBtns[index];
        const cancelBtn = cancelBtns[index];
        const editControl = editControls[index];

        // Enable edit
        editBtn.addEventListener("click", () => {
        	originalText = document.querySelectorAll(".editable-section textarea.content")[index].value;
            textSection.contentEditable = "true";
            textSection.focus();
            textSection.classList.add("textArea-editable");
            editControl.classList.remove("hidden");
        });

        // Save text
        saveBtn.addEventListener("click", () => {
            textSection.contentEditable = "false";
            textSection.innerHTML = document.querySelectorAll(".editable-section textarea.content")[index].value;
            editControl.classList.add("hidden");
            textSection.classList.remove("textArea-editable");
        });

        // Cancel edit
        cancelBtn.addEventListener("click", () => {
            textSection.innerHTML = originalText;
            textSection.contentEditable = "false";
            editControl.classList.add("hidden");
            textSection.classList.remove("textArea-editable");
        });
    });



    const copyBtns = document.querySelectorAll(".copy-btn");
    const tickBtns = document.querySelectorAll(".tick-btn");

    copyBtns.forEach((copyBtn, index) => {
        copyBtn.addEventListener("click", () => {
            // Assuming each copyBtn is associated with a tickBtn in the same order
            const tickBtn = tickBtns[index];

            // Copy text to clipboard
            navigator.clipboard.writeText(textSections[index].value).then(() => {
                // Animation to tick button
                tickBtn.classList.remove("fade-out");
                copyBtn.classList.add("fade-out");
                setTimeout(() => {
                    copyBtn.style.display = "none";
                    tickBtn.style.display = "inline-block";
                    tickBtn.classList.add("fade-in");
                }, 500); // Wait for fade-out to finish

                // Set timeout to switch back to copy button
                setTimeout(() => {
                    tickBtn.classList.remove("fade-in");
                    tickBtn.classList.add("fade-out");
                    setTimeout(() => {
                        tickBtn.style.display = "none";
                        copyBtn.style.display = "inline-block";
                        copyBtn.classList.remove("fade-out");
                        copyBtn.classList.add("fade-in");
                    }, 500); // Wait for fade-out to finish
                }, 2000); // Time until switch back
            }).catch(err => {
                console.error("Failed to copy text: ", err);
            });
        });
    });





    document.querySelectorAll(".rewrite-btn").forEach((btn, index) => {
        btn.addEventListener("click", () => {
            // Assuming the index corresponds to the related rewriteDiv
            document.querySelectorAll(".rewrite-reason-div")[index].style.display = "flex";
        });
    });

    document.querySelectorAll(".btn-rewrite").forEach((btn, index) => {
        btn.addEventListener("click", () => {
            const rewritePrompt = document.querySelectorAll(".rewrite-reason-div textarea")[index].value;
            console.log(rewritePrompt);
            if (rewritePrompt) {
                // Prepare and make the API call with the rewrite prompt
                const templateIndex = localStorage.getItem("selectedTemplateIndex"); // Assuming templateIndex is stored in localStorage
                const paragraphElement = document.querySelectorAll(".editable-section textarea.content")[index];
                sendRewriteRequest(index, templateIndex, rewritePrompt, paragraphElement);
            }
            document.querySelectorAll(".rewrite-reason-div")[index].style.display = "none";
        });
    });
    
    
    // Function to send the rewrite request
      // Modified version to use prepareFormDataBasedOnTemplate directly
      // Object to store rewrite responses for each paragraph
const rewriteResponses = {};

// Modified sendRewriteRequest function
// Modified version to use prepareFormDataBasedOnTemplate directly
function sendRewriteRequest(paragraphIndex, templateIndex, rewritePrompt, paragraphElement) {
const wrapper = paragraphElement.closest(".paragraph-wrapper");
          addSkeletonLoader(wrapper); // Add loader
	console.log(rewritePrompt);
    
    if (!(paragraphIndex in rewriteResponses)){
    	rewriteResponses[paragraphIndex] = [];
        rewriteResponses[paragraphIndex].push(paragraphElement.value);
    }
    
    
    //const templateIndex = localStorage.getItem("selectedTemplateIndex");
    // Get the formData with the correct experience and other data based on template and paragraphIndex
    const formData = prepareFormDataBasedOnTemplate(paragraphIndex, templateIndex);
    if (!formData) {
        console.error("Failed to prepare form data");
        return;
    }
    
    // Add the rewrite prompt to the formData
    formData.append("rewrite_prompt", rewritePrompt);

    // Debugging: Print all formData values
    console.log("FORMDATA OF REWRITE:");
    for (const [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    

    

fetch(sac_ajax_object.ajax_url, {
    method: "POST",
    credentials: "same-origin",
    headers: {
        "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams(formData)
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log("FORMDATA OF REWRITEeeeee:");
        for (const [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        // Update the paragraph content with the new response
        const content = data.data.choices[0].message.content;
        // Assuming paragraphElement is now a <textarea>, use .value
        // Ensure paragraphElement is correctly defined and accessible in this scope
        paragraphElement.value = content;
        const length = rewriteResponses[paragraphIndex].push(content);
        console.log(length);
        updateParagraphContent(paragraphIndex, length - 1);
    } else {
        console.error("Error:", data.data.message);
    }
})
.catch(error => {
    console.error("Fetch error:", error);
})
.finally(() => {
    
    // You might need to ensure this or pass it as an argument to the relevant function
    // Remove skeleton loader, make sure this operation is defined in the current context
    removeSkeletonLoader(wrapper); // Assuming removeSkeletonLoader is a defined function
});
}


// Function to update paragraph content with rewrite response
function updateParagraphContent(paragraphIndex, responseIndex) {
    const paragraphElement = document.querySelectorAll(".editable-section textarea.content")[paragraphIndex];
    const navigationElement = document.querySelectorAll(".response-navigation")[paragraphIndex];
    console.log(rewriteResponses);
    if (rewriteResponses[paragraphIndex] && rewriteResponses[paragraphIndex][responseIndex]) {
        paragraphElement.value = rewriteResponses[paragraphIndex][responseIndex];
        navigationElement.querySelector(".response-counter").textContent = `${responseIndex + 1} / ${rewriteResponses[paragraphIndex].length}`;
    }
}

// Adding event listeners to navigation arrows
document.querySelectorAll(".arrow-right").forEach((arrow, index) => {
    arrow.addEventListener("click", () => {
        const currentCounter = parseInt(arrow.parentElement.querySelector(".response-counter").textContent.split(" / ")[0], 10);
        if (currentCounter <= rewriteResponses[index].length) {
            updateParagraphContent(index, currentCounter); // Navigate to next rewrite response
        }
    });
});

document.querySelectorAll(".arrow-left").forEach((arrow, index) => {
    arrow.addEventListener("click", () => {
        const currentCounter = parseInt(arrow.parentElement.querySelector(".response-counter").textContent.split(" / ")[0], 10);
        console.log(currentCounter);
        if (currentCounter > 1) {
        	console.log("Hello");
            updateParagraphContent(index, currentCounter - 2); // Navigate to previous rewrite response
        }
    });
});



    // ---------------------------- API CALLS ------------------------------
    
    
</script>

    <!---------------------------------- Page 1 API CALL ------------------------------------ -->
    
    

    <script>
		
        function saveCvToDatabase() {
    const cvText = localStorage.getItem("cv_data");
    fetch(sac_ajax_object.ajax_url, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            "action": "sac_handle_update_cv",
            "cvText": cvText,
            "security": sac_ajax_object.security // Optional: for nonce verification
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(data); // Success message
            // Optionally, clear the textarea or show a success message
        } else {
            console.error(data); // Error message
            // Optionally, show an error message to the user
        }
    })
    .catch(error => console.error("Error:", error));
}
        
        
    </script>


    <!-- ------------------------------------------ page 2 API CALL ------------------------------------- -->

    <script>

        function generateMotivation() {
          const textArea = document.getElementById("position1");
          const container = textArea.closest(".textarea-container"); // Get the closest parent with the class .textarea-container
          console.log("Generating motivation...");

          container.classList.add("loading"); // Add loading class to show the loader

          const formData = new FormData();
          formData.append("action", "generateWithAI");
          formData.append("prompt", 0); // Using 0 as a static example
          // formData.append("cv_data", localStorage.getItem("cv_data"));
          formData.append("job_data", localStorage.getItem("jobpost_data"));
          console.log("PRINTING FORM DATA OF MOTIVATION");
		  for (const value of formData.values()) {
 			 console.log(value);
		}
          fetch(sac_ajax_object.ajax_url, {
              method: "POST",
              body: formData
          })
          .then(response => response.json())
          .then(response => {
              if (response.success) {
                  console.log("Success:", response.data);
                  textArea.value = response.data.choices[0].message.content; // This line might need adjustment based on actual response structure
              } else {
                  console.error("Server Error:", response.data);
              }
          })
          .catch(error => {
              console.error("Network Error:", error);
          })
          .finally(() => {
              container.classList.remove("loading"); // Remove loading class to hide the loader
          });
      }




        function generateExperience(){
			const textArea = document.getElementById("position2");
          const container = textArea.closest(".textarea-container"); // Get the closest parent with the class .textarea-container
          console.log("Generating motivation...");

          container.classList.add("loading"); // Add loading class to show the loader

          const formData = new FormData();
          formData.append("action", "generateWithAI");
          formData.append("prompt", 1); // Using 0 as a static example
          formData.append("cv_data", localStorage.getItem("cv_data"));
          formData.append("job_data", localStorage.getItem("jobpost_data"));
          //console.log("MEOWOWWW");
			for (const value of formData.values()) {
 			 console.log(value);
		}
          fetch(sac_ajax_object.ajax_url, {
              method: "POST",
              body: formData
          })
          .then(response => response.json())
          .then(response => {
              if (response.success) {
                  console.log("Success:", response.data);
                  textArea.value = response.data.choices[0].message.content; // This line might need adjustment based on actual response structure
              } else {
                  console.error("Server Error:", response.data);
              }
          })
          .catch(error => {
              console.error("Network Error:", error);
          })
          .finally(() => {
              container.classList.remove("loading"); // Remove loading class to hide the loader
          });
        }   


        function generatePersonality() {
			const textArea = document.getElementById("position3");
          const container = textArea.closest(".textarea-container"); // Get the closest parent with the class .textarea-container
          console.log("Generating motivation...");

          container.classList.add("loading"); // Add loading class to show the loader

          const formData = new FormData();
          formData.append("action", "generateWithAI");
          formData.append("prompt", 2); 
          formData.append("job_data", localStorage.getItem("jobpost_data"));
		for (const value of formData.values()) {
 			 console.log(value);
		}
          fetch(sac_ajax_object.ajax_url, {
              method: "POST",
              body: formData
          })
          .then(response => response.json())
          .then(response => {
              if (response.success) {
              	
                  console.log("Success:", response.data);
                  textArea.value = response.data.choices[0].message.content; // This line might need adjustment based on actual response structure
              } else {
                  console.error("Server Error:", response.data);
              }
          })
          .catch(error => {
              console.error("Network Error:", error);
          })
          .finally(() => {
              container.classList.remove("loading"); // Remove loading class to hide the loader
          });
        }

	


    </script>

	


    
    <! -------------------------- page 4 API CALL ----------------------------------- >
	
    
    <script> 
                function getLocalStorageItem(itemKey) {
            return localStorage.getItem(itemKey);
        }


       function addSkeletonLoader(targetElement) {
          if (!targetElement.querySelector(".loader-overlay")) {
              const overlay = document.createElement("div");
              overlay.className = "loader-overlay";
              const skeletonLoader = document.createElement("div");
              skeletonLoader.className = "skeleton-loader";
              overlay.appendChild(skeletonLoader);
              targetElement.appendChild(overlay);
          }
      }

      function removeSkeletonLoader(targetElement) {
          const overlay = targetElement.querySelector(".loader-overlay");
          if (overlay) {
              overlay.remove();
          }
      }

      function sendAPIRequest(formData, paragraphElement) {
          const wrapper = paragraphElement.closest(".paragraph-wrapper");
          addSkeletonLoader(wrapper); // Add loader

          fetch(sac_ajax_object.ajax_url, {
              method: "POST",
              body: formData,
          })
          .then(response => response.json())
          .then(data => {
              removeSkeletonLoader(wrapper); // Remove loader
              if (data.success && data.data.choices.length > 0) {
                  const messageContent = data.data.choices[0].message.content;
                  paragraphElement.value = messageContent;
              } else {
                  paragraphElement.value = "Invalid response structure";
              }
          })
          .catch(error => {
              removeSkeletonLoader(wrapper); // Ensure removal even in case of error
              console.error(`Error fetching data:`, error);
              paragraphElement.value = "Error loading content. Please try again later.";
          });
      }




        function initiateAPICalls() {
              const templateIndex = getLocalStorageItem("selectedTemplateIndex");
              const textareas = document.querySelectorAll(".editable-section textarea.content");

              textareas.forEach((textarea, index) => {
              		const wrapper = document.createElement("div");
                    wrapper.className = "paragraph-wrapper";
                    textarea.parentNode.insertBefore(wrapper, textarea);
                    wrapper.appendChild(textarea);
                  // Skip making the call for the 4th paragraph if templateIndex != 0
                  if (templateIndex != "0" && index === 3) {
                      console.log("Skipping API call for the 4th paragraph as templateIndex != 0");
                      return; // Continue to the next iteration of the loop
                  }

                  const formData = prepareFormDataBasedOnTemplate(index, templateIndex);
                   console.log("FORMDATA OF TEMPLATE: ");
			for (const value of formData.values()) {
 			 console.log(value);
             console.log("\n");
		}
                  if (formData) {
                      sendAPIRequest(formData, textarea);
                  } else {
                      console.error("FormData preparation failed.");
                  }
              });
          }
        
        function prepareFormDataBasedOnTemplate(paragraphIndex, templateIndex) {
          const formData = new FormData();
          const cvData = getLocalStorageItem("cv_data"); // Retrieve from localStorage
          const jobData = getLocalStorageItem("jobpost_data"); // Retrieve from localStorage
          let promptIndex = paragraphIndex;
          let experience = "";

          
          switch (templateIndex) {
              case "0":
                  if (paragraphIndex === 0 || paragraphIndex === 3) {
                      experience = getLocalStorageItem("personality");
                  } else if (paragraphIndex === 2) {
                      experience = getLocalStorageItem("experience");
                  }
                  break;
              case "1":
                  if (paragraphIndex === 0 || paragraphIndex === 2) {
                      experience = getLocalStorageItem("personality");
                  } else if (paragraphIndex === 1) {
                      experience = getLocalStorageItem("experience");
                  }
                  break;
              case "2":
                  if (paragraphIndex === 0 || paragraphIndex === 2) {
                      experience = getLocalStorageItem("personality");
                  } else if (paragraphIndex === 1) {
                      experience = getLocalStorageItem("experience");
                  }
                  break;
              default:
                  console.error("Invalid template index.");
                  return null;
          }

          // Setup FormData common for any templateIndex
          // Right before you append other form data
		  formData.append("action", "generateWithAIEnhanced"); // This should match your WP add_action hook

          formData.append("cv_data", cvData);
          formData.append("job_data", jobData);
          formData.append("templateIndex", templateIndex);
          formData.append("prompt", promptIndex.toString());
         
          
          if (experience != "") {
              formData.append("experience", experience);
          }
			
  
          return formData;
      }

    
    
    </script>

</body>

</html>';



    return $html;
}

add_shortcode("redesign_application", "redesign_application");





//------------------------------------------------------------------------------------ Redesign Application Page 2----------------------------------------------------------------------------------------------------------

function generateWithAI()
{
    if (!isset ($_POST['job_data']) || empty ($_POST['job_data'])) {
        wp_send_json_error('Job data is required.');
        wp_die();
    }
    require ("prompts/application_redesign_page_2.php");
    $prompt = $prompts[intval($_POST['prompt'])];
    $job_data = $_POST['job_data']; // Since job_data is compulsory
    $max_token = $tokens[intval($_POST['prompt'])];
    // Initialize the messages array with the prompt
    $messages = [
        ["role" => "user", "content" => $prompt],
        ["role" => "user", "content" => $job_data] // Add job_data since it's compulsory
    ];

    // Conditionally add cv_data to the messages array if it's set and not empty
    if (isset ($_POST['cv_data']) && !empty ($_POST['cv_data'])) {
        $cv_data = $_POST['cv_data'];
        $messages[] = ["role" => "user", "content" => $cv_data];
    }

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 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', // Ensure secure API key handling
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo-0125',
            'temperature' => 0.1,
            'max_tokens' => $max_token, // Use the dynamically determined max_tokens
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 30, // Adjusted timeout for practicality
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // Required to terminate immediately and return a proper response
}

add_action("wp_ajax_generateWithAI", "generateWithAI");

//-------------------------------- Redesign application Page 4 ----------------------------------------------------------------------

function generateWithAIEnhanced()
{
    // Ensure cv_data, job_data, and templateIndex are provided
    if (
        !isset ($_POST['cv_data']) || empty ($_POST['cv_data']) ||
        !isset ($_POST['job_data']) || empty ($_POST['job_data']) ||
        !isset ($_POST['templateIndex']) || $_POST['templateIndex'] === '' ||
        !isset ($_POST['prompt']) || $_POST['prompt'] === ''
    ) {
        wp_send_json_error('CV data, job data, template index, and prompt selection are required.');
        wp_die();
    }
    //Max tokens for each template per row

    // Define your 2D array of prompts. Adjust the array structure as needed.

    require ("prompts/application_redesign_page_4.php");
    $templateIndex = intval($_POST['templateIndex']);
    $promptIndex = intval($_POST['prompt']);

    // Ensure templateIndex and promptIndex are within the bounds of the prompts array
    if (!isset ($prompts[$templateIndex]) || !isset ($prompts[$templateIndex][$promptIndex])) {
        wp_send_json_error('Invalid template index or prompt selection.');
        wp_die();
    }

    $maxTokens = $tokens[$templateIndex][$promptIndex]; // Retrieve max tokens based on template and prompt
    $selectedPrompt = $prompts[$templateIndex][$promptIndex];
    $cv_data = $_POST['cv_data'];
    $job_data = $_POST['job_data'];

    // Initialize the messages array with the selected prompt, cv_data, and job_data
    $messages = [
        ["role" => "user", "content" => $selectedPrompt],
        ["role" => "user", "content" => $cv_data], // CV data is mandatory
        ["role" => "user", "content" => $job_data], // Job data is mandatory
    ];

    // Conditionally add experience to the messages array if it's set and not empty
    if (isset ($_POST['experience']) && !empty ($_POST['experience'])) {
        $experience = $_POST['experience'];
        $messages[] = ["role" => "user", "content" => $experience];
    }

    // Conditionally add experience to the messages array if it's set and not empty
    if (isset ($_POST['rewrite_prompt']) && !empty ($_POST['rewrite_prompt'])) {
        $rewrite_prompt = $_POST['rewrite_prompt'];
        $messages[] = ["role" => "user", "content" => $rewrite_prompt];
    }

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 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', // Ensure secure API key handling
        ],
        'body' => json_encode([
            'model' => 'gpt-4-0125-preview',
            'temperature' => 0.1,
            'max_tokens' => $maxTokens, // Use the dynamically determined max_tokens
            'messages' => $messages,
            'messages' => $messages,
            'stream' => false,
        ]),
        'timeout' => 500,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }

    wp_die(); // Required to terminate immediately and return a proper response
}

add_action("wp_ajax_generateWithAIEnhanced", "generateWithAIEnhanced");
// Uncomment below if the action should also be accessible to non-logged-in users
// add_action("wp_ajax_nopriv_generateWithAIEnhanced", "generateWithAIEnhanced");



