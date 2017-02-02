<?php

namespace App\Controllers;

use \App\Models\Entry;

class Entries extends BasePrivate {
        
    public function entries() {       
        $entryId = !empty($_POST['entry_id']) ? $_POST['entry_id'] : false;
        if (isset($_POST['action'])) { 
            $entry = Entry::findOneBy(['author' => $this->user, 'id' => $entryId]);
            
            if ($_POST['action'] == 'edit_entry') {
                if (!empty($_POST['text']) || !empty($_POST['attachments'])) {
                    if (!$entry) $entry = new Entry;
                    $entry->text = !empty($_POST['text']) ? strip_tags($_POST['text']) : '';
                    $entry->attachments = !empty($_POST['attachments']) ? $_POST['attachments'] : [];
                    $entry->attachment_preview_settings = [
                        'first_row_height_percent' => $_POST['first_row_height_percent'],
                        'secondary_rows_height_percent' => $_POST['secondary_rows_height_percent']
                    ];
                    $entry->author = $this->user;
                    $entry->save();
                }
            } elseif ($_POST['action'] == 'delete_entry') {
                if (!$entry) return;
                $entry->deleted = true;
                $entry->save();
                $entryId = false;
            }
        }
        
        $searchCriteria = !empty($_GET['search']) ? $_GET['search'] : '';
        $searchText = trim(preg_replace("/#[\w|\p{L}]+/u", '', $searchCriteria));
        
        $hashTags = [];
        preg_match_all("/#([\w|\p{L}]+)/u", $searchCriteria, $matches);
        if (!empty($matches[1])) {
            $hashTags = $matches[1];
        }
        
        $limit = 5;
        $offset = ($this->getPage() - 1) * $limit;
        $query = Entry::getSearchQuery($entryId, $this->user, $searchText, $hashTags);
        $total = \DoctrineExtensions\Paginate\Paginate::getTotalQueryResults($query);
        $entries = $query->setMaxResults($limit)->setFirstResult($offset)->getResult();
        
        $this->data['search_criteria'] = $searchCriteria;
        $this->data['show_load_more_button'] = $offset + $limit < $total;
        $this->data['current_page'] = $this->getPage();
        $this->data['entries'] = $entries;
        $this->view('entries');
    }
}