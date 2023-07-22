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
  $multiline = false;
  foreach (file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES) as $no => $line) {
    $line_t = trim($line);
    if (empty($line_t) || $line_t[0] === '#')
      continue;
    $indent = '' !== preg_replace('/^(\s*)\S.*$/', '\1', $line);
    $line_end = substr($line_t, -1);
    if (!$multiline) {
      if (!$indent && $line_end === ':') {
        $label = trim(substr($line_t, 0, -1));
        Localization::$DICT[$label] = [];
        $current = &Localization::$DICT[$label];
      }
      else if ($indent) {
        [$locale, $text] = explode(':', $line_t, 2);
        $locale = rtrim($locale);
        $text = ltrim($text);
        if ($line_end === '"')
          $current[$locale] = substr($text, 1, -1);
        else {
          $current[$locale] = substr($text, 1);
          $multiline = true;
        }
        $locales[] = $locale;
      }
      else
        throw new \Exception('Syntax error in localization file `' . $file . '` in line ' . $no);
    }
    else {
      if ($line_end !== '"')
        $current[$locale] .= "\n" . $line_t;
      else {
        $current[$locale] .= "\n" . rtrim(substr($line_t, 0, -1));
        $multiline = false;
      }
    }
  }
  Localization::$DICT_LOCALES = array_unique($locales);
}

function SET_LOCALE($locale = null, $fallback = 'en-US')
{
  if (empty($locale)) {
    $locale = null;
    $header_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
    if (!empty($header_lang)) {
      if (function_exists('locale_accept_from_http'))
        $locale = locale_accept_from_http($header_lang);
      else
        $locale = explode(',', $header_lang)[0];
    }
    if (empty($locale)) {
      if (!empty($fallback)) {
        $locale = $fallback;
        $fallback = null;
      }
      else
        throw new \Exception('Cannot set an empty locale');
    }
  }

  $loc = str_replace(['_', '.'], '-', $locale);
  $locales = [$loc];
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

function GET_LOCALE($num_parts = -1)
{
  if (empty(Localization::$LOCALE))
    throw new \Exception('Locale not set');

  if ($num_parts === -1)
    return Localization::$LOCALE_FULL;
  if ($num_parts === 0)
    return Localization::$LOCALE[0];
  return array_slice(Localization::$LOCALE, 0, $num_parts);
}

function INIT_JS($func_name = 'L')
{
  if (is_null(Localization::$DICT))
    throw new \Exception('Localization file not loaded');
  if (empty(Localization::$LOCALE))
    throw new \Exception('Locale not set');

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
    function <?=$func_name?>(label, ...args)
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
      if (substr($item_locale, 0, strlen($locale)) === $locale)
        return $item[$item_locale];
  }
  return $item[array_key_first($item)];
}

function L($label, ...$args)
{
  if (!array_key_exists($label, Localization::$DICT))
    return $label;

  $str = _find_in(Localization::$DICT[$label], $label);
  if (count($args))
    return sprintf($str, ...$args);
  return $str;
}