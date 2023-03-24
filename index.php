<?php

//phpinfo();
require './vendor/autoload.php';
include_once 'src/fndb.php';

$timings = [
    'setup' => [],
    'insert' => [],
    'read' => [],
    'delete' => [],
    'memory' => [],
    'total' => []
];

function quick_test() {
    $fndb = new fndb('test');
    $fndb->set(1, ['data', 'value']);
    echo '<pre>' . print_r($fndb->get(1), true) . '</pre>';
    $fndb->delete(1);
    echo '<pre>' . print_r($fndb->get(1), true) . '</pre>';

    $fndb->set('person/1', ['John', 'Larsen']);
    echo '<pre>' . print_r($fndb->get('person/1', ['first', 'last']), true) . '</pre>';
    echo '<br><br><hr/><br><br>';
    exit();
}

//quick_test();
$amount_to_test = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : 5000;
//echo '<br><br><hr/><br><br><h2>' . $amount_to_test . ' verdier</h2>';
$randoms = [];
for ($i = 0; $i < $amount_to_test; $i++) {
    $randoms[] = [random_string(), random_string()];
}

$start_time = microtime(true);
$memory_before = memory_get_usage();
$fndb = new fndb('benchmark');
$setup_time = microtime(true);
//Inserts
if (isset($_REQUEST['insert'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $fndb->set('person/' . $i, $randoms[$i]);
    }
}

//fnDB - No Cache
$insert_time = microtime(true);
//Reads
if (isset($_REQUEST['read'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $value = $fndb->get('person/' . $i, ['first', 'last']);
    }
}
$read_time = microtime(true);

//Deletes
if (isset($_REQUEST['delete'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $fndb->delete('person/' . $i);
    }
}
$delete_time = microtime(true);
$memory_after = memory_get_usage();

$timings['setup'][] = round($setup_time - $start_time, 3);
$timings['insert'][] = round($insert_time - $setup_time, 3);
$timings['read'][] = round($read_time - $insert_time, 3);
$timings['delete'][] = round($delete_time - $read_time, 3);
$timings['total'][] = round($delete_time - $start_time, 3);
$timings['memory'][] = $memory_after - $memory_before;

//fnDB -> Cache
$start_time = microtime(true);
$memory_before = memory_get_usage();
$fndb = new fndb('benchmark');
$fndb->cache(true);
$setup_time = microtime(true);
//Inserts
if (isset($_REQUEST['insert'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $fndb->set('names/' . $i, $randoms[$i]);
    }
}

$insert_time = microtime(true);
//Reads
if (isset($_REQUEST['read'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $value = $fndb->get('names/' . $i, ['first', 'last']);
    }
}
$read_time = microtime(true);

//Deletes
if (isset($_REQUEST['delete'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $fndb->delete('names/' . $i);
    }
}
$delete_time = microtime(true);
$memory_after = memory_get_usage();

$timings['setup'][] = round($setup_time - $start_time, 3);
$timings['insert'][] = round($insert_time - $setup_time, 3);
$timings['read'][] = round($read_time - $insert_time, 3);
$timings['delete'][] = round($delete_time - $read_time, 3);
$timings['total'][] = round($delete_time - $start_time, 3);
$timings['memory'][] = $memory_after - $memory_before;

//Redis
$start_time = microtime(true);
$memory_before = memory_get_usage();
$redis = new Predis\Client();
$setup_time = microtime(true);
//Inserts
if (isset($_REQUEST['insert'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $redis->set('person/' . $i, implode('__', $randoms[$i])); //, false, time() + 60 * 60 * 24 * 30
    }
}
$insert_time = microtime(true);
//Reads
if (isset($_REQUEST['read'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $value = $redis->get('person/' . $i);
        $data = [
            'id' => $i,
            'db' => 'person',
            'path' => 'person/' . $i,
            'data' => $value ? array_combine(['first', 'last'], explode('__', $value)) : []
        ];
    }
}
$read_time = microtime(true);

//Deletes
if (isset($_REQUEST['delete'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $redis->del('person/' . $i, 0);
    }
}
$delete_time = microtime(true);
$memory_after = memory_get_usage();
$timings['setup'][] = round($setup_time - $start_time, 3);
$timings['insert'][] = round($insert_time - $setup_time, 3);
$timings['read'][] = round($read_time - $insert_time, 3);
$timings['delete'][] = round($delete_time - $read_time, 3);
$timings['total'][] = round($delete_time - $start_time, 3);
$timings['memory'][] = $memory_after - $memory_before;

//MySQL
$start_time = microtime(true);
$memory_before = memory_get_usage();
$db = new mysqli('localhost', 'root', '', 'fndb');
$setup_time = microtime(true);
if (isset($_REQUEST['insert'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $db->query('INSERT IGNORE INTO person (id, first, last) VALUES (' . $i . ', "' . $randoms[$i][0] . '", "' . $randoms[$i][1] . '")');
    }
}
$insert_time = microtime(true);
//Reads
if (isset($_REQUEST['read'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $row = mysqli_fetch_assoc($db->query('SELECT * FROM person WHERE id = ' . $i));
        $data = [
            'id' => $i,
            'db' => 'person',
            'path' => '',
            'data' => $row
        ];
    }
}
$read_time = microtime(true);

//Deletes
if (isset($_REQUEST['delete'])) {
    for ($i = 0; $i < $amount_to_test; $i++) {
        $db->query('DELETE FROM person WHERE id = ' . $i);
    }
}
$delete_time = microtime(true);
$memory_after = memory_get_usage();
$timings['setup'][] = round($setup_time - $start_time, 3);
$timings['insert'][] = round($insert_time - $setup_time, 3);
$timings['read'][] = round($read_time - $insert_time, 3);
$timings['delete'][] = round($delete_time - $read_time, 3);
$timings['total'][] = round($delete_time - $start_time, 3);
$timings['memory'][] = $memory_after - $memory_before;
/*
  echo '
  Setup: ' . round($setup_time - $start_time, 3) . 's<br>
  Inserts: ' . round($insert_time - $setup_time, 3) . 's<br>
  Reads: ' . round($read_time - $insert_time, 3) . 's<br>
  Deletes: ' . round($delete_time - $read_time, 3) . 's<br>
  <b>Total: ' . round($delete_time - $start_time, 3) . 's</b><br>
  Memory: ' . ($memory_after - $memory_before) . '<br>';
 */

function random_string() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < rand(10, 100); $i++) {
        $string .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $string;
}

$rows = [];
foreach ($timings as $doing => $row) {
    $columns = ['<td>' . ucfirst($doing) . '</td>'];
    $lowest = min($row);
    $highest = max($row);
    foreach ($row as $column) {
        $columns[] = '<td class="' . ($lowest == $column ? 'best' : ($highest == $column ? 'worst' : '')) . '">' . $column . ($doing == 'memory' ? 'b' : 's') . '</td>';
    }
    $rows[] = '<tr>' . implode('', $columns) . '</tr>';
}
//?XDEBUG_PROFILE=1
echo '
<style>
    span, h2 {
        font-size: 300%;
    }
    a:hover, td:hover, th:hover {
        background: #00FA9A !important;
    }
    .table {
        width: 100%;
        font-size: 300%;
    }
    .table th {
        background: #ccc;
        font-weight: bold;
        padding: 4px;
    }
    .table td:not(:first-of-type) {
        padding: 4px;
        text-align: center;
    }
    .table td:first-of-type {
        padding: 4px;
        font-weight: bold;
    }
    .table td.best {
        background: #e8fbe8;
    }
    .table td.worst {
        background: #fbe8e8;
    }
    .table td {
        border-bottom: 1px solid #888;
    }
</style>
<span><a href="http://127.0.0.5/?amount=' . $amount_to_test . '">Setup</a><b> | </b><a href="http://127.0.0.5/?amount=' . $amount_to_test . '&insert&read&delete">Insert, Read & Delete</a><b> | </b><a href="http://127.0.0.5/?amount=' . $amount_to_test . '&insert">Insert</a><b> | </b><a href="http://127.0.0.5/?amount=' . $amount_to_test . '&read">Read</a><b> | </b><a href="http://127.0.0.5/?amount=' . $amount_to_test . '&delete">Delete</a></span>
<h2>Test with ' . $amount_to_test . ' values.</h2>
<table class="table">
    <thead>
        <tr>
            <th></th><th>fnDB</th><th>fnDB (cache)</th><th>Redis</th><th>MySQL</th>
        </tr>
    </thead>
    <tbody>
        ' . implode('', $rows) . '
    </tbody>
</table>';

/**
 * $array = ['folder1', 'folder2', 'folder3'];
$array2 = ['folder1', 'folder4'];

$resulting_array = [];
print_r($resulting_array);
$resulting_array = run_array($resulting_array, $array);
print_r($resulting_array);
$resulting_array = run_array($resulting_array, $array2);
print_r($resulting_array);

function run_array(&$resulting_array, $array = null){
	if($array){
		$key = array_shift($array);

		if(!isset($resulting_array[$key])){
			$resulting_array[$key] = [];
			run_array($resulting_array[$key], $array);
		} else {
			run_array($resulting_array[$key], $array);
		}

	}
	return $resulting_array;
};
 */