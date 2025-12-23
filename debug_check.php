<?php
require 'vendor/autoload.php';

try {
    echo "--- Properties ---\n";
    $class = new ReflectionClass('Spatie\GoogleCalendar\Event');
    foreach ($class->getProperties() as $prop) {
        $visibility = 'public';
        if ($prop->isProtected()) $visibility = 'protected';
        if ($prop->isPrivate()) $visibility = 'private';
        echo $prop->getName() . " [" . $visibility . "]\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
