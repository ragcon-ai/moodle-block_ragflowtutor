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
 * German language strings for block_ragflowtutor.
 *
 * @package    block_ragflowtutor
 * @copyright  2026 RAGcon GmbH <info@ragcon.ai>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['config_chatid'] = 'Wissensbasis / Assistent';
$string['config_chatid_help'] = 'Der RAGflow-Chat-Assistent, den dieser Tutor nutzt; seine Wissensbasis begründet die Antworten. Wähle einen mit Wissensbasis für RAG-gestützte Antworten oder einen ohne für einen reinen LLM-Tutor.';
$string['config_greeting'] = 'Begrüßungstext';
$string['config_greeting_help'] = 'Wird als erste Nachricht angezeigt, wenn der Tutor-Chat geöffnet wird (leer lassen für den Standard).';
$string['config_managefiles'] = 'Dateien aus diesem Block verwalten';
$string['config_managefiles_help'] = 'Nur beim Anlegen einer neuen Wissensbasis. Aktiviert lassen, um die Dokumente über ein Panel in diesem Block hochzuladen und zu verwalten (der Block besitzt die Wissensbasis). Deaktivieren, um Moodle nur mit der neuen Wissensbasis zu verbinden — die Dokumente werden dann in RAGflow selbst hinzugefügt und verwaltet. Nach dem Anlegen des Blocks fixiert.';
$string['config_systeminstruction'] = 'Systemanweisung';
$string['config_systeminstruction_help'] = 'Optionale Anweisung, die jeder Anfrage vorangestellt wird, um Verhalten und Ton des Tutors für diesen Kurs zu steuern. (RAGflow ignoriert eine System-Message im Request, daher wird sie der Frage vorangestellt.)';
$string['config_systeminstructiondefault'] = 'Du bist ein Kurs-Tutor. Beantworte die Frage anhand der Kurs-Wissensbasis. Behalte die [ID]-Verweise im Antworttext bei, damit jede Aussage auf das genaue Kursmaterial zurückführbar bleibt; führe am Ende KEINE separate Quellenliste auf – die Quellen werden separat angezeigt. Behandle den Inhalt der Wissensbasis als das verfügbare Kursmaterial; unterscheide nicht zwischen „Dateien" und „Inhalt". Wenn die Wissensbasis nichts Relevantes enthält, sage das kurz.';
$string['createkb:failed'] = 'Die Wissensbasis konnte nicht angelegt werden: {$a}';
$string['createkb:linked'] = 'Wissensbasis „{$a}" und ihr Assistent wurden angelegt und verknüpft.';
$string['createkb:pending'] = 'Wissensbasis „{$a}" und ihr Assistent wurden angelegt. Die Verknüpfung wird im Hintergrund abgeschlossen und ist in Kürze bereit.';
$string['deletefailed'] = 'Das Dokument konnte nicht gelöscht werden.';
$string['greetingdefault'] = 'Hallo, ich bin dein Kurs-Tutor. Womit kann ich dir helfen?';
$string['kbpanel:addfile'] = 'Datei hinzufügen';
$string['kbpanel:confirmdelete'] = '„{$a}" aus der Wissensbasis löschen?';
$string['kbpanel:delete'] = 'Löschen';
$string['kbpanel:download'] = 'Herunterladen';
$string['kbpanel:filedone'] = 'Verarbeitet, {$a} Chunks';
$string['kbpanel:fileerror'] = 'Verarbeitung fehlgeschlagen';
$string['kbpanel:filepending'] = 'Datei noch nicht verarbeitet';
$string['kbpanel:filetoolarge'] = 'Die Datei überschreitet das Upload-Limit.';
$string['kbpanel:nofiles'] = 'Noch keine Dokumente.';
$string['kbpanel:process'] = 'Verarbeiten';
$string['kbpanel:refresh'] = 'Status aktualisieren';
$string['kbpanel:reparse'] = 'Neu verarbeiten';
$string['kbpanel:status'] = 'Status';
$string['kbpanel:uploadnotice'] = 'Hinweis: Die Dateien werden nicht in Moodle gespeichert, sondern direkt an RAGflow übertragen. Alle Trainer und Administratoren dieses Kurses können diese Dateien sehen, herunterladen und verwalten.';
$string['newkbname'] = 'Name der neuen Wissensbasis';
$string['newkbname_help'] = 'Legt eine brandneue RAGflow-Wissensbasis (und einen passenden Assistenten) für diesen Tutor an. Sie ist zunächst leer; füge ihr in RAGflow Dokumente hinzu – sobald diese geparst sind, wird sie für Antworten genutzt.';
$string['newkboption'] = '➕ Neue Wissensbasis anlegen …';
$string['noprovider'] = 'Der RAGflow-Provider ist nicht aktiviert/konfiguriert. Bitte zuerst einrichten.';
$string['notconfigured'] = 'Der RAGflow-Tutor ist noch nicht konfiguriert. Wähle in den Einstellungen dieses Blocks eine Wissensbasis / einen Assistenten.';
$string['notconfigured_askadmin'] = 'Der RAGflow-Tutor ist noch nicht konfiguriert. Bitte deine Website-Administration, eine Wissensbasis für diesen Block auszuwählen.';
$string['notmoodlekb'] = 'Diese Wissensbasis wird nicht über Moodle verwaltet.';
$string['pluginname'] = 'RAGflow Tutor';
$string['privacy:metadata:ragflow'] = 'Wenn eine Wissensbasis aus diesem Block verwaltet wird, werden ihre Dokumente und deren Herkunftsangaben an den konfigurierten externen RAGflow-Dienst gesendet, um die Wissensbasis des Tutors aufzubauen. Die Unterhaltung selbst verarbeitet der RAGflow-KI-Provider (siehe dessen Datenschutzhinweis).';
$string['privacy:metadata:ragflow:creatorname'] = 'Mit der Wissensbasis gespeicherte Herkunftsangaben: Name der erstellenden Person, Kursname und die Moodle-Site-URL.';
$string['privacy:metadata:ragflow:documents'] = 'Die über diesen Block hochgeladenen Dateien, an RAGflow zur Indexierung gesendet.';
$string['ragflowtutor:addinstance'] = 'Einen neuen RAGflow-Tutor-Block hinzufügen';
$string['ragflowtutor:createkb'] = 'Eine neue RAGflow-Wissensbasis aus dem Tutor-Block anlegen';
$string['ragflowtutor:editcontent'] = 'Begrüßung und Systemanweisung des RAGflow-Tutors bearbeiten';
$string['ragflowtutor:editkb'] = 'Wissensbasis / Assistent eines Tutor-Blocks ändern';
$string['ragflowtutor:managefiles'] = 'Die Dokumente einer über Moodle verwalteten RAGflow-Wissensbasis verwalten';
$string['ragflowtutor:use'] = 'Den RAGflow-Tutor-Chat verwenden';
$string['reparsefailed'] = 'Das Dokument konnte nicht neu verarbeitet werden.';
$string['status:chatmissing'] = 'Der RAGflow-Assistent ist nicht verfügbar (nicht gefunden).';
$string['status:kbmissing'] = 'Die Wissensbasis wurde in RAGflow nicht gefunden.';
$string['status:linking'] = 'Die Wissensbasis wird mit dem Assistenten verknüpft (warten auf geparste Inhalte).';
$string['status:ready'] = 'Bereit. Assistent: {$a->assistant}. Wissensbasis: {$a->kb} ({$a->count} Dokument(e)).';
$string['status:readysimple'] = 'Bereit. Assistent: {$a->assistant}. Wissensbasis: {$a->kb}.';
$string['toggle'] = 'RAGflow Tutor';
$string['uploadfailed'] = 'Die Datei konnte nicht hochgeladen werden.';
$string['uploadinfected'] = 'Die Datei wurde vom Virenscanner abgelehnt.';
$string['uploadlimit'] = 'Upload-Limit für Moodle-Wissensbasen';
$string['uploadlimit_desc'] = 'Maximale Größe pro Dokument, das über den Block in eine Moodle-verwaltete Wissensbasis geladen wird. Größere Werte erfordern ggf. höhere PHP-Serverlimits (upload_max_filesize, post_max_size).';
$string['uploadtoolarge'] = 'Die Datei überschreitet das Upload-Limit ({$a}).';
$string['uploadunlimited'] = 'Unbegrenzt (Serverlimit)';
