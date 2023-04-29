<?php namespace LOCALIZATION;

$LOCALIZATION_LOCALE = null;

function INIT_FROM_FILE($file)
{
  if (defined('LOCALIZATION_DICT')) {
    trigger_error('Localization already initialized');
    return;
  }
  $dict = [];
  $current = [];
  foreach (file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES) as &$line) {
    $lineLOCALIZATION = trim($line);
    if ($lineLOCALIZATION == '' || $lineLOCALIZATION[0] == '#')
      continue;
    if ($line[0] != ' ') {
      $dict[substr($lineLOCALIZATION, 0, -1)] = [];
      $current = &$dict[array_key_last($dict)];
    }
    else {
      [$lang, $text] = explode(': ', substr($line, 1), 2);
      $current[$lang] = substr($text, 1, -1);
    }
  }
  define('LOCALIZATION_DICT', $dict);
}

function SET_LOCALE($locale)
{
  global $LOCALIZATION_LOCALE;
  if (!in_array($locale, array_keys(current(LOCALIZATION_DICT))))
    trigger_error('Locale `' . $locale . '` not found');
  else
    $LOCALIZATION_LOCALE = $locale;
}

function INIT_JS()
{
  if (!defined('LOCALIZATION_DICT')) {
    trigger_error('Localization not initialized');
    return;
  }
  global $LOCALIZATION_LOCALE;
  if (is_null($LOCALIZATION_LOCALE)) {
    trigger_error('Locale not set');
    return;
  }
?><script>
    let LOCALIZATION_DICT = {<?php
      $dict = array_map(fn($item) => $item[$LOCALIZATION_LOCALE], LOCALIZATION_DICT);
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
  global $LOCALIZATION_LOCALE;
  if (count($args))
    return sprintf(LOCALIZATION_DICT[$label][$LOCALIZATION_LOCALE], ...$args);
  return LOCALIZATION_DICT[$label][$LOCALIZATION_LOCALE];
}