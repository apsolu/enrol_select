<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'enrol_select', language 'en'.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Inscription par voeux';
$string['pluginname_desc'] = 'Le plugin d\'inscription par voeux permet aux utilisateurs de choisir les cours qu\'ils veulent suivre. Les cours peuvent être protégés par différents critères (période d\'inscription, taille de la liste principale, cohortes, etc).';

// Permissions.
$string['select:config'] = 'Configurer les instances d\''.strtolower($string['pluginname']);
$string['select:enrol'] = 'Inscrire des utilisateurs';
$string['select:manage'] = 'Gérer les utilisateurs inscrits';
$string['select:unenrol'] = 'Désinscrire du cours les utilisateurs';
$string['select:unenrolself'] = 'Se désinscrire du cours';

$string['cohortonly'] = 'Seulement les membres de la cohorte';
$string['cohortnonmemberinfo'] = 'Only members of cohort \'{$a}\' can enrol.';
$string['enrolstartdate'] = 'Date d\'ouverture des inscriptions';
$string['enrolenddate'] = 'Date de fermeture des inscriptions';

$string['max_places'] = 'Nombre de places sur liste principale';

$string['overviewtitle'] = 'Inscription aux activités';

$string['activities_list'] = 'Liste des créneaux par activité';
$string['activities'] = 'Activités sportives';
$string['no_activities'] = 'Aucune activité ouverte aux inscriptions.';

$string['complements_list'] = 'Liste des activités complémentaires';
$string['complements'] = 'Activités complémentaires';
$string['no_complements'] = 'Aucune activité complémentaire ouverte aux inscriptions.';

$string['max_waiting_places'] = 'Nombre de places sur liste complémentaire';
$string['role'] = 'Rôle attribué par défaut';

$string['status'] = 'Activer cette méthode d\'inscription';
$string['general'] = 'Général';

$string['types'] = 'Cours évalué';
$string['wishes'] = 'Voeux';
$string['roles'] = 'Rôles';
$string['prices'] = 'Tarification';
$string['colleges'] = 'Population';

$string['accepted_list'] = 'Liste des étudiants acceptés';
$string['accepted_description'] = 'Liste des étudiants acceptés en cours. Ils ont accès aux forums et aux documents du cours. Une liste de toutes les sessions à venir est également indiquée sur leur page d\'accueil.';
$string['main_list'] = 'Liste principale';
$string['main_description'] = 'Liste des étudiants sur liste principale. Ils n\'ont accès ni aux forums, ni aux documents du cours. Seule la première session du cours est indiquée leur page d\'accueil.';
$string['main_list_registered'] = 'Inscrit sur liste principale';
$string['wait_list'] = 'Liste complémentaire';
$string['wait_description'] = 'Liste des étudiants sur liste complémentaire. Ils n\'ont accès ni aux forums, ni aux documents du cours. Seule la première session du cours est indiquée leur page d\'accueil.';
$string['wait_list_registered'] = 'Inscrit sur liste complémentaire';
$string['refused_list'] = 'Liste des étudiants refusés';
$string['error:enrol'] = 'Impossible de vous inscrire à ce cours';

$string['enrolment'] = 'Inscription';
$string['enrol'] = 'S\'inscrire';
$string['unenrol'] = 'Se désinscrire';
$string['edit_enrol'] = 'Modifier son type d\'inscription';

$string['unenroled'] = 'Désinscrit';

$string['canntenrol'] = 'canntenrol';

$string['rolename_and_price'] = '{$a->rolename} - {$a->price} {$a->currency}';
$string['rolename_and_price_free'] = '{$a} - gratuit';

$string['event_user_moved'] = 'Utilisateur déplacé';
$string['full_registration'] = 'Inscription complète';

$string['maxwishes'] = 'Nombre de voeux maximum';
$string['maxwishes_help'] = 'Nombre de voeux maximum par défaut attribué à un utilisateur n\'étant pas clairement identifié dans le système d\'information ; n\'appartenant à aucune population définie.';

$string['manage_nofity'] = 'Envoyer une notification aux étudiants sélectionnés';
$string['manage_notification_0'] = 'Vous avez été retiré de la liste des étudiants acceptés.';
$string['manage_notification_2'] = 'Vous avez été retiré de la liste principale.';
$string['manage_notification_3'] = 'Vous avez été retiré de la liste complémentaire.';
$string['manage_notification_4'] = 'Vous avez été retiré de la liste des étudiants refusés.';

$string['move_to_accepted'] = 'Déplacer dans la liste des étudiants acceptés';
$string['move_to_main'] = 'Déplacer dans la liste principale';
$string['move_to_wait'] = 'Déplacer dans la liste complémentaire';
$string['move_to_refused'] = 'Déplacer dans la liste des étudiants refusés';

$string['goto'] = 'Déplacer de la liste {$a->from} vers la liste {$a->to}';
$string['list_accepted'] = 'acceptée';
$string['list_main'] = 'principale';
$string['list_wait'] = 'complémentaire';

$string['message_accepted_to_main'] = 'Bonjour,

Vous avez été déplacé de la liste des inscrits à la liste principale.

Cordialement,';
$string['message_accepted_to_wait'] = str_replace('principale', 'complémentaire', $string['message_accepted_to_main']);
$string['message_main_to_accepted'] = str_replace(array('des inscrits', 'complémentaire'), array('principale', 'des inscrits'), $string['message_accepted_to_main']);
$string['message_main_to_wait'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_wait']);
$string['message_wait_to_accepted'] = str_replace('principale', 'complémentaire', $string['message_main_to_accepted']);
$string['message_wait_to_main'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_main']);

$string['notifystudents'] = 'Notifier les étudiants';
$string['message'] = 'Message';
$string['enrolcoursesubject'] = '[{$a->fullname}] Situation de votre inscription';

$string['eula'] = 'Certificat d\'aptitude au sport';
$string['eula_help'] = 'Texte présenté à tous les étudiants avant toute inscription à une activité sportive et nécessitant une acceptation de leur part.';

// Variables pour l'export csv des listes étudiantes.
$string['age'] = 'Âge';
$string['birthday'] = 'Date de naissance';
$string['sex'] = 'Sexe';
$string['register_type'] = 'Type d\'inscription';
$string['paid'] = 'Carte sport payée';
$string['list'] = 'Liste';

// Licence FFSU.
$string['federation_required'] = 'FFSU (obligatoire)';
$string['federation_optional'] = 'FFSU (facultatif)';

$string['html_role_notifications'] = '<div class="alert alert-info"><p>Seule la première inscription par type de voeux est payante. Les inscriptions suivantes sont gratuites.</p>'.
    '<p>Example: 1 inscription en libre est également à 30€. 2 inscriptions en libre sont égales aussi à 30€ (et non 60€).</p></div>';

$string['error_no_left_slot'] = 'Il n\'y a pas plus de place disponible pour ce cours.';
$string['error_reach_wishes_limit'] = 'Vous avez atteint le nombre maximum de voeux de type {$a}.';
$string['error_cannot_enrol'] = 'Vous ne pouvez pas vous inscrire à ce cours.';
$string['error_no_role'] = 'Vous devez sélectionner au moins un rôle pour pouvoir inscrire un utilisateur.';
