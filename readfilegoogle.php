<?php
include('./simplehtmldom/simple_html_dom.php');
include('user_agents.php');
include('fake_search_terms.php');

$val = getopt("t:p:f:s:o:");

$search_fixed_term = $val['t'];
$position = $val['p'];
$names_filename = $val['f'];
$output_filename = $val['o'];
// $sleep_interval = intval($val['s']);

$names_list = file($names_filename);
define("TERMS", $search_fixed_term);
define("POSITION", $position);

if($sleep_interval)
{
    define("SLEEP", $sleep_interval);
} else{
    define("SLEEP", 90);
}

$global_counter = 0;
$counter_interval = 5;
$global_requests = 0;
$sleep_interval = 4;

foreach($names_list as $e) {
    sleep($sleep_interval);
    makeFakeSearchRequest($fake_search_terms, ++$global_counter, $counter_interval);
    $searchTerm = prepareSearchTerm($e);
    // $url = prepareSearchTermUrl($searchTerm, 'https://www.google.com/search?num=100&en&q=%s"&meta=""');
    // echo $searchTerm;
    $url = prepareSearchTermUrlTemp($searchTerm);

    $context = getUserAgentHeader($user_agents);

    echo $url . "\n";

    $html = file_get_html($url, false, $context);

    while($html == false OR empty($html)) 
    {
        // sleep(SLEEP);
        randomSleeper($sleep_interval);
        echo "\nwe are getting blocked, so we are waiting!\n";
        $html = file_get_html($url, false, $context);
    } 

    // echo $html;
    if($html !== false AND !empty($html)){
        $actual_result = '';
        $total_result = '';
        foreach($html->find('div[id=result-stats]') as $el)
        {
            $val = $el->innertext;
            preg_match('/(?<=About ).*(?= results)/', $val, $matches);
            $total_result = $matches[0];
        }

        if($total_result > 100)
        {
            // $second_page_url = "https://www.google.com/search?q=" .  . "&num=100&start=100";
            // $second_page_url = prepareSearchTermUrl($searchTerm, 'https://www.google.com/search?q=%s&num=100&start=100');
            $second_page_url = prepareSearchTermUrlTemp2($searchTerm);
            $second_page_html = file_get_html($second_page_url, false, $context);

            foreach($second_page_html->find('p[id=ofr] ') as $el)
            {
                $val = $el->children(0)->innertext;
                preg_match('/(?<=In order to show you the most relevant results, we have omitted some entries very similar to the ).*(?= already displayed)/', $val, $matches);
                $actual_result = $matches[0];
            }
        } 
        else 
        {
            foreach($html->find('p[id=ofr] ') as $el)
            {
                $val = $el->children(0)->innertext;
                preg_match('/(?<=In order to show you the most relevant results, we have omitted some entries very similar to the ).*(?= already displayed)/', $val, $matches);
                $actual_result = $matches[0];
            }
        }
        
    } else {
        array_push($remaining_array, rtrim($e)); 
    }
    
    $string_to_file = trim($e) .  ", " . $url .  ", " . filter_var($total_result, FILTER_SANITIZE_NUMBER_INT) .  ", " . filter_var($actual_result, FILTER_SANITIZE_NUMBER_INT);
    writeStringToFile($output_filename, $string_to_file);
    // sleep(5);
}

function writeStringToFile($filename, $string)
{ 
    echo "wrting to file\n";
    file_put_contents($filename, $string . "\n", FILE_APPEND);
}

function makeFakeSearchRequest($fake_search_terms, $global_counter, $counter_interval) {
    if($global_counter % $counter_interval == 0 ){
        $url = $fake_search_terms[rand(0, count($fake_search_terms))];
        file_get_html($url, false, $context);
    }    
}

function prepareSearchTermUrl($searchTerm, $url) {
   
    // $searchWords = prepareSearchTerm($e);
    $final_url = sprintf($url, $searchWords);
    // $final_url = 'https://www.google.com/search?num=100&en&q="' . $searchWords . '"&meta=""';

    // $final_url = 'http://localhost/server.html';
    return $final_url;
}

function prepareSearchTermUrlTemp($searchTerm) {
   
    // $searchWords = prepareSearchTerm($e);
    // $final_url = sprintf($url, $searchWords);
    $final_url = 'https://www.google.com/search?num=100&en&q="' . $searchTerm . '"&meta=""';
    // $final_url = 'http://localhost/server.html';
    return $final_url;
}

function prepareSearchTermUrlTemp2($searchTerm) {
   
    // $searchWords = prepareSearchTerm($e);
    // $final_url = sprintf($url, $searchWords);
    // $final_url = 'https://www.google.com/search?num=100&en&q="' . $searchWords . '"&meta=""';
    $final_url = "https://www.google.com/search?q=" . $searchTerm . "&num=100&start=100";

    // $final_url = 'http://localhost/server.html';
    return $final_url;
}

function prepareSearchTerm($e) {
    $e = trim($e);
    if(POSITION == "pre"){
        $e_with_fixed_terms = TERMS . " " . $e;
    } else {
        $e_with_fixed_terms = $e . " " . TERMS;
    }

    $searchWords = str_replace(' ', '+', $e_with_fixed_terms);

    return $searchWords;
}

function getUserAgentHeader($user_agents)
{
    $opts = array(
        'http'=>array(
          'header'=>"User-Agent:" . $user_agents[rand(0, count($user_agents))] . "\r\n"
        )
      );

    return stream_context_create($opts);
}

function randomSleeper(&$sleep_interval) {
    if($sleep_interval < 20)
    {
        $sleep_interval += rand(5, 15);        
    } 
    else 
    {
        $sleep_interval = 5;
    }
    sleep($sleep_interval);
}

// writeArrayToFile('remaining.csv', $remaining_array);
?>