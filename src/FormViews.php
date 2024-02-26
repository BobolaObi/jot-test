<?php
/**
 *
 */

namespace Legacy\Jot;

use Legacy\Jot\Utils\DB;

class FormViews
{
    /**
     * Gets the list of recently updated forms
     * @param  $username
     * @param  $number // [optional]
     * @return
     */
    static function getRecentForms($username, $number = 5)
    {

        # This only brings the latest updated forms
        $res = DB::read("
            SELECT * FROM `forms` 
            WHERE `username`=':username' 
            AND (
                `status` is NULL 
                OR  `status` = 'ENABLED' 
                OR `status` = ''
            )
            ORDER BY `updated_at` DESC 
            LIMIT 0, #limit",
            $username, $number);

        return $res->result;


        # This is disabled now. But this was bringing the latest 5 forms who received a submission
        # we may use this later so don't delete this
        $allForms = DB::read("
            SELECT DISTINCT form_id
            FROM (
                SELECT form_id, created_at
                FROM submissions
                WHERE form_id
                IN (
                
                    SELECT id
                    FROM forms
                    WHERE username = ':username'
                )
                ORDER BY created_at DESC
            ) AS fc
            LIMIT 0 , :limit", $username, $number);

        $forms = [];
        foreach ($allForms->result as $line) {
            $form = DB::read('SELECT * FROM `forms` WHERE `id`=#id', $line['form_id']);

            if ($form->first['count'] < 0) {
                $form->first['count'] = Form::updateSubmissionCount($form->first['id']);
            }

            if ($form->first['new'] < 0) {
                $form->first['new'] = Form::updateNewSubmissionCount($form->first['id']);
            }

            $forms[] = $form->first;
        }

        return $forms;
    }
}
