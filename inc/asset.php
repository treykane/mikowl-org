<?php
/**
 * CACHE BUSTING
 *
 * asset('css/main.css') returns "css/main.css?v=1730412345", where the number
 * is the file's last-modified time. Saving or deploying a changed file gives it
 * a new URL, so browsers drop their cached copy and download the new one.
 * There is no version number to remember to bump.
 *
 * Path segments are URL-encoded, so the result is safe to drop straight into
 * an HTML attribute (file names like "mikey donut.jpg" become "mikey%20donut.jpg").
 */
function asset($path)
{
    $path = ltrim($path, '/');
    $url = implode('/', array_map('rawurlencode', explode('/', $path)));
    $mtime = @filemtime(dirname(__DIR__) . '/' . $path);

    return $mtime ? $url . '?v=' . $mtime : $url;
}
