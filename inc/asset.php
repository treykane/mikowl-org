<?php
/**
 * CACHE BUSTING
 *
 * asset('css/main.css') returns "css/main.css?v=3f2a9c1b", where the code is a
 * fingerprint of the file's contents. Change a file and its URL changes with
 * it, so browsers drop their cached copy and download the new one. There is
 * no version number to remember to bump.
 *
 * A file that hasn't changed keeps its URL, even through a deploy that writes
 * every file afresh (which resets every last-modified time). That keeps it
 * cached in browsers, and keeps image URLs steady for Google Images, which
 * knows each image by its URL.
 *
 * Path segments are URL-encoded, so the result is safe to drop straight into
 * an HTML attribute (file names like "mikey donut.jpg" become "mikey%20donut.jpg").
 */
function asset($path)
{
    $path = ltrim($path, '/');
    $url = implode('/', array_map('rawurlencode', explode('/', $path)));
    $algo = in_array('xxh3', hash_algos(), true) ? 'xxh3' : 'md5'; // xxh3 (PHP 8.1+) is several times faster
    $hash = @hash_file($algo, dirname(__DIR__) . '/' . $path);

    return $hash ? $url . '?v=' . substr($hash, 0, 8) : $url;
}
