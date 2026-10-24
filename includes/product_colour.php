<?php

/** A display swatch for the catalogue's stored primary colour. */
function f2bProductColourHex($colour): string
{
    static $palette = [
        'black' => '#242424',
        'blue' => '#527faf',
        'brown' => '#704b39',
        'coral orange' => '#df785b',
        'cream' => '#e9dcc4',
        'green' => '#54805a',
        'grey' => '#8b9295',
        'maroon' => '#752b42',
        'navy blue' => '#293c5d',
        'olive green' => '#727950',
        'pink' => '#d988aa',
        'red' => '#ad3f43',
        'rust brown' => '#9b593d',
        'teal blue' => '#347f94',
        'teal green' => '#367e71',
        'white' => '#fafafa',
        'yellow' => '#d2b341',
        // Earlier catalogue values remain displayable if products are added later.
        'navy' => '#293c5d',
        'coral' => '#d98875',
        'olive' => '#727950',
        'ink' => '#303a43',
        'mist' => '#bfc8c7',
        'sand' => '#c7ad87',
        'slate' => '#68757f',
        'stone' => '#b1aa9c',
        'sage' => '#9ba88d',
        'light blue' => '#a4c6df',
    ];

    $key = strtolower(trim((string) $colour));
    return $palette[$key] ?? '#929b93';
}
