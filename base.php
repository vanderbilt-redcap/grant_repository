<?php
/** Author: Jon Scherdin */

if (
    (strpos($_SERVER['HTTP_HOST'], 'redcap.vanderbilt.edu') !== false)
    || (strpos($_SERVER['HTTP_HOST'], 'redcap.vumc.org') !== false)
) {
    $grantsProjectId = 27635;  // original
    // $grantsProjectId = 165634;   // backup from 2023-01-23
    $userProjectId = 27636;
    $eventId = 52818;
} elseif (
    (strpos($_SERVER['HTTP_HOST'], 'redcaptest.vanderbilt.edu') !== false)
    || (strpos($_SERVER['HTTP_HOST'], 'redcaptest.vumc.org') !== false)
) {
    $grantsProjectId = 266;
    $userProjectId = 265;
    $eventId = 1089;
} else {
    # SJP's localhost does not have imagick installed and so won't rasterize MS Word into PDFs
    $grantsProjectId = 122;
    $userProjectId = 124;
    $eventId = 145;
}

require_once("../../redcap_connect.php");

function sanitize($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}

function getChoices($metadata)
{
    $choicesStrs = array();
    $multis = array("checkbox", "dropdown", "radio");
    foreach ($metadata as $row) {
        if (in_array($row['field_type'], $multis) && $row['select_choices_or_calculations']) {
            $choicesStrs[$row['field_name']] = $row['select_choices_or_calculations'];
        } elseif ($row['field_type'] == "yesno") {
            $choicesStrs[$row['field_name']] = "0,No|1,Yes";
        } elseif ($row['field_type'] == "truefalse") {
            $choicesStrs[$row['field_name']] = "0,False|1,True";
        }
    }
    $choices = array();
    foreach ($choicesStrs as $fieldName => $choicesStr) {
        $choicePairs = preg_split("/\s*\|\s*/", $choicesStr);
        $choices[$fieldName] = array();
        foreach ($choicePairs as $pair) {
            $a = preg_split("/\s*,\s*/", $pair);
            if (count($a) == 2) {
                $choices[$fieldName][$a[0]] = $a[1];
            } elseif (count($a) > 2) {
                $a = preg_split("/,/", $pair);
                $b = array();
                for ($i = 1; $i < count($a); $i++) {
                    $b[] = $a[$i];
                }
                $choices[$fieldName][trim($a[0])] = implode(",", $b);
            }
        }
    }
    return $choices;
}

function searchForTerms($pid, $eventId, $terms, $record = null)
{
    $fields = ["record_id", "grants_number", "grants_pi", "grants_abstract", "grants_thesaurus", "grants_file"];
    $fieldsToInspect = ["grants_abstract" => "Abstract", "grants_thesaurus" => "Terms or Public Health Relevance"];
    $searchRecord = null;
    if ($record) {
        $searchRecord = [$record];
    }
    $redcapData = \REDCap::getData($pid, "json-array", $searchRecord, $fields);

    $foundItems = [];
    foreach ($terms as $term) {
        if ($term) {
            $term = strtolower($term);
            $len = strlen($term);
            foreach ($redcapData as $row) {
                foreach ($fieldsToInspect as $field => $displayField) {
                    $words = sanitize($row[$field]);
                    $wordsInLC = strtolower($words);
                    $pos = strpos($wordsInLC, $term);
                    if ($pos !== false) {
                        $pi = sanitize($row["grants_pi"]);
                        $textWithSpan = "<span style='background-color: #f4ff00;'>".substr($words, $pos, $len)."</span>";
                        $text = substr_replace($words, $textWithSpan, $pos, $len);
                        $url = "download.php?p=$pid&id=" .
                            sanitize($row['grants_file']) . "&s=&page=register_grants&record=" . sanitize($row['record_id']) . "&event_id=" .
                            $eventId . "&field_name=grants_file";
                        $foundItems["<a href='$url'>".sanitize($row['grants_number'])." ($pi) - ".$displayField."</a>"] = $text;
                    }
                }
            }
        }
    }
    return $foundItems;
}

function makeSearchHTML($foundItems)
{
    $html = "<h2>".count($foundItems)." Found Items</h2>";
    foreach ($foundItems as $awardNo => $text) {
        $html .= "<h4>$awardNo</h4>";
        $html .= "<p>$text</p>";
    }
    return $html;
}
