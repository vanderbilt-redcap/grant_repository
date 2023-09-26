<?php

require_once("base.php");

$redcapData = \REDCap::getData($grantsProjectId, "json-array", [$record]);
$searchTerms = [
    "Implementation Science",
    "Implementation Research",
    "Quality Improvement",
    "Acceptability",
    "Adoption",
    "Appropriateness",
    "Feasibility",
    "Fidelity",
    "Sustainability",
    "Barriers",
    "Facilitators",
    "PDSA cycle",
    "Qualitative",
    "Penetration",
    "Mixed Methods",
    "Mixed-Methods",
    "Implementation Strategy",
    "Implementation Strategies",
    "Implementation Framework",
    "RE-AIM",
    "CFIR",
    "Consolidated Framework for Implementation Research",
    "Implementation Effectiveness",
    "Implementation Outcome",
    "Program Evaluation",
];
$to = "robyn.tamboli@vumc.org,isaac.schlotterbeck@vumc.org";
$foundItems = searchForTerms($grantsProjectId, $event_id, $searchTerms, $record);
if (!empty($foundItems)) {
    $html = "<h1>Grant Repository Record $record</h1><p><strong>Search Terms: </strong>".implode(", ", $searchTerms)."</p>".makeSearchHTML($foundItems);
    \REDCap::email($to, "noreply.grantRepository@vumc.org", "Grant Repository matches for CCQIR", $html);
}