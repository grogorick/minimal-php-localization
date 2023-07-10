<?php namespace LOCALIZATION;

class Localization
{
  public static $LOCALE_FULL = null;
  public static $LOCALE = [];

  public static $DICT = null;
  public static $DICT_LOCALES = null;
}

function INIT_FROM_FILE($file)
{
  if (!file_exists($file))
    throw new \Exception('Localization file `' . $file . '` not found');

  Localization::$DICT = [];
  $locales = [];
  $current = [];
  foreach (file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES) as $line) {
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

function SET_LOCALE($locale = null, $fallback = 'en-US')
{
  if (empty($locale)) {
    $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (empty($locale)) {
      if (!empty($fallback)) {
        $locale = $fallback;
        $fallback = null;
      }
      else
        throw new \Exception('Cannot set an empty locale');
    }
  }
  $locale = str_replace(['_', '.'], '-', $locale);

  $locales = [$loc = $locale];
  while (true) {
    $p = strrpos($loc, '-');
    if ($p !== false)
      $locales[] = $loc = substr($loc, 0, $p);
    else
      break;
  }

  $new_locale = [];
  foreach ($locales as $loc)
    if (in_array($loc, Localization::$DICT_LOCALES))
      $new_locale[] = $loc;

  if (empty($new_locale)) {
    if (!empty($fallback))
      SET_LOCALE($fallback, null);
    else
      throw new \Exception('Locale `' . $loc . '` not found');
  }
  else {
    Localization::$LOCALE_FULL = $locale;
    Localization::$LOCALE = $new_locale;
  }
}

function GET_LOCALE()
{
  return Localization::$LOCALE_FULL;
}

function INIT_JS()
{
  if (is_null(Localization::$DICT)) {
    throw new \Exception('Localization file not loaded');
    return;
  }
  if (empty(Localization::$LOCALE)) {
    throw new \Exception('Locale not set');
    return;
  }
?><script>
    let LOCALE = '<?=GET_LOCALE()?>';
    let LOCALIZATION_DICT = {<?php
      $dict = [];
      array_walk(Localization::$DICT, function(&$item, $label) use (&$dict)
      {
        $dict[] = $label . ': "' . _find_in($item, $label) .'"';
      });
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

function _find_in($item, $label)
{
  if (is_null($item))
    return $label;

  foreach (Localization::$LOCALE as $locale) {
    foreach (array_keys($item) as $item_locale)
      if (str_starts_with($item_locale, $locale))
        return $item[$item_locale];
  }
  return $item[array_key_first($item)];
}

function L($label, ...$args)
{
  $str = _find_in(Localization::$DICT[$label], $label);
  if (count($args))
    return sprintf($str, ...$args);
  return $str;
}