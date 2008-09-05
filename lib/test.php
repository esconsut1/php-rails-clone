<?

include('swish.php');

// Get the top results
$s = new Query('../index/main_index.index');
$s->term('xtodayx');
$s->limit(100);
$s->filter('tag_id', 1, 1);
$s->get_hits();
$s->get_results();
$current = $s->serps;

print_r($current);
?>
