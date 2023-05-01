# MINIMAL PHP LOCALIZATION

## Localization file format (yaml-like)
``` yaml
# COMMENT
LABEL:
 LOCALE-1: "Text for locale 1"
 LOCALE-2: "Text for locale 2"


# Examples
hello:
 en: "Hello!"
 de-DE: "Hallo!"
 de-AT: "Griaß di!"

points:
 en: "%s Points"
 de: "%s Punkte"
progress:
 en: "Step %s of %s"
 de: "Schritt %s von %s"
```

## Initialization
**PHP**
``` php
<?php
require('localization/localization.php');
LOCALIZATION\INIT_FROM_FILE('localization.yaml');
LOCALIZATION\SET_LOCALE($_GET['lang'] ?? null);
use function LOCALIZATION\L;
?>
```
- load localization dictionary from file
- set locale, e.g., from URL parameter
- make localization function `L()` available in PHP

**JS**
``` php
<?=LOCALIZATION\INIT_JS()?>
```
- generate a &lt;script&gt; tag to make localization function `L()` available in JS

## Usage
**PHP**
``` php
<?=L('LABEL')?>
```

**JS**
``` html
<script>L('LABEL')</script>
```

**Examples**
- `L('hello')` &mdash; Get localized string
- `L('points', 42)` &mdash; Get localized string, with a contained `%s` replaced by the supplied argument
- `L('progress', 99, 100)` &mdash; Any number of arguments can be supplied, e.g., two

## Locale matching
Given a requested `label` and the current locale, e.g., `de-DE`, the localized entries for that label in the dictionary are (in that order) searched for locales that
- exactly match `de-DE`
- start with `de-DE`
- exactly match `de`
- start with `de`

The *first* entry is selected if no locale matched.  
The `label` itself is returned if it's not contained in the dictionary.