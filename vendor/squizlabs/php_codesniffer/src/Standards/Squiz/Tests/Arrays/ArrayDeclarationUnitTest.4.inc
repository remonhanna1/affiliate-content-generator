<?php

// Intentional parse error (missing T_AS in foreach).
// This should be the only test in this file.
// Testing that the code preventing the sniff to act on short lists inside a foreach doesn't
// interfere with the rest of sniff when the `as` keyword is missing.

foreach ([$a , $b])