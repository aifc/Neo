<?php

function mark_field_invalid($field_id, $validation_message, $form) {
    foreach ( $form['fields'] as $field ) {
        if ( $field->id == $field_id ) {
            $field->failed_validation = true;
            $field->validation_message = $validation_message;
            break;
        }
    }
}
