<?php
namespace Manga;

trait DateConverter {
    protected function convertDate($date) {
        return date('Y-m-d H:i:s', strtotime($date));
    }
}
