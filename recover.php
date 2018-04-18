<?php

require_once("../../redcap_connect.php");

$records = array(
'2018 - Williams DJ - Risk Stratification and Decision Support to Improve Care and Outcomes',
'2015 - Cassat JE - The Impact of Hypoxia on Satphylococcus aureus metabolism',
'231 - Georgiev I - Neutralization fingerprinting analysis of polyclonal antibody responses',
'230 - Hernandez A - Enhancement of innate anti-microbial immunity using novel',
'229 - Pettit A - Multi-level social and behavioral determinants of health',
'228 - Austin ED - Strogen signaling and energy metabolism in pulmonary arterial hypertension',
'227 - Richmond BW',
'226 - Stolldorf DP IMPLEMENTING AND SUSTAINING COMPLEX INTERDISCIPLINARY HEALTHCARE INTERVENTIONS',
'225 Brittain EL - A Mobile Health Intervention in PAH',
'224 - Gogliotti RG - Normalizing E:I imbalance in Rett Syndrome',
'223 - Gaddy JA - Determining the contribution of zinc deficiency',
'222 - Shaver CM - Mechanisms of Airspace inflammation',
'221 - Kirabo A - Role of Salt',
'220 - Yiadom MY - Improving the Criteria that Trigger an Early ECG to Diagnose STEMI',
'219 Rebeiro PF - The HIV Care Continuum and Health Policy',
'217 Kroncke BM - SCN5A (Nav1.5): Predicing the Consequences of Missense...',
'216 Hughey J - A Multi-scale Approach to the Mammalian Circadian System and Its Role in Human Health',
'204 - Ely EW - Diversity Supplement (2014)',
'203 - Aune TM R21',
'202 Wilson SM - An Adaptive Semantic Paradigm for Valid and Reliable Language Mapping in Aphasia',
'201 Wilson SM - Functional neuroimaging of language processing in primary progressive aphasia',
'199 Drake WP - Diversity Supplement',
'197 Birdee GS - Breathing Interventions for Relaxation',
'197 - Drake WP - Diversity Supplement 1',
'196 Shibao C Racial Differences in Vagal Control',
'195 Jordan LC - MRI-based Quantitative Brain Oxygen',
'194 Castilho JL - The Dynamics of HIV, Aging, and T Lymphocyte Exhaustion',
'193 Gordon RL - Rhythm in Atypical Language Development',
'192 Nechuta SJ - Triple Negative Breast Cancers R03',
'191 Beach LB - Sexual and Gender Minority Status to Health Care Providers',
'190 Talbot HK - US Ambulatory Influenza Vaccine Effectiveness',
'189 Cascio CJ - Peripersonal space representation as a basis for social deficits in autism',
'188 Winder DG - Noradrenergic Regulation in the BNST (R01)',
'187 France DJ - The Impact of Non-Routine Events on Neonatal Safety in the Perioperative Environment',
'186 Gewin L - TGF-beta Pathways that Protect Epithelia in Chronic Renal Injury (R01)',
'185 Ormseth MJ - Functional Impact of HDL Transport of miRNA in Rheumatoid Arthritis (K23)',
'185 Mayberry LS - Targeting Family Members',
'184 Ward MJ - Enhancing Inter-FAcility Transfer for Patients with Acute Myocardial Infarction',
'181 Kropski JA - DNA-Damage Repair in Pulmonary Fibrosis',
'180 Monroe, T - Brain Activation and Pain Reports in People with Alzheimer\'s Disease',
'179 Page-McCaw, AW - WNT/WG Extracellular Ligand Distribution and Regulation',
'178 - Reynolds WS - Afferent Hyperactivity Mechanisms in Overactive Bladder Syndrome',
'173 - Interprofessional Perinatal Consults',
);

foreach ($records as $record) {
	$sql = "SELECT pk, sql_log FROM redcap_log_event WHERE pk = '".db_real_escape_string($record)."' AND project_id = 27635 AND event = 'UPDATE'";
	$q = db_query($sql);
	echo db_num_rows($q)."<br>";
	while ($row = db_fetch_assoc($q)) {
		echo json_encode($row)."<br>";
	}
}
