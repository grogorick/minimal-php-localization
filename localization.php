<?php namespace LOCALIZATION;

class Localization
{
  public static $LOCALE_FULL = null;
  public static $LOCALE = null;

  public static $DICT = null;
  public static $DICT_LOCALES = null;
}

function INIT_FROM_FILE($file)
{
  Localization::$DICT = [];
  $locales = [];
  $current = [];
  foreach (file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES) as &$line) {
    $line_t = trim($line);
    if ($line_t == '' || $line_t[0] == '#')
      continue;
    if ($line[0] != ' ') {
      Localization::$DICT[substr($line_t, 0, -1)] = [];
      $current = &Localization::$DICT[array_key_last(Localization::$DICT)];
    }
    else {
      [$locale, $text] = explode(': ', substr($line, 1), 2);
      $current[$locale] = substr($text, 1, -1);
      $locales[] = $locale;
    }
  }
  Localization::$DICT_LOCALES = array_unique($locales);
}

function SET_LOCALE($locale)
{
  $locales = [$loc = $locale];
  $p = strpos($loc, '.');
  if ($p !== false) {
    $locales[] = $loc = substr($loc, 0, $p);
  }
  $p = strpos($loc, '-');
  if ($p !== false) {
    $locales[] = $loc = substr($loc, 0, $p);
  }

  foreach ($locales as $loc)
    if (in_array($loc, Localization::$DICT_LOCALES)) {
      Localization::$LOCALE = $loc;
      Localization::$LOCALE_FULL = $locale;
      return;
    }
  trigger_error('Locale `' . $loc . '` not found');
}

function GET_LOCALE()
{
  return Localization::$LOCALE_FULL;
}

function INIT_JS()
{
  if (is_null(Localization::$DICT)) {
    trigger_error('Localization not initialized');
    return;
  }
  if (is_null(Localization::$LOCALE)) {
    trigger_error('Locale not set');
    return;
  }
?><script>
    let LOCALIZATION_DICT = {<?php
      $dict = array_map(fn($item) => $item[Localization::$LOCALE], Localization::$DICT);
      array_walk($dict, function(&$val, $key) { $val = $key . ': "' . $val .'"'; });
      echo implode(', ', $dict);
      ?>};
    function L(label, ...args)
    {
      let str = LOCALIZATION_DICT[label];
      if (str === undefined)
        return label;
      for (const arg of args)
        str = str.replace('%s', arg);
      return str;
    }
  </script>
<?php
}

function L($label, ...$args)
{
  if (count($args))
    return sprintf(Localization::$DICT[$label][Localization::$LOCALE], ...$args);
  return Localization::$DICT[$label][Localization::$LOCALE];
}