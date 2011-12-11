<?php
class Counters {
	private $link;
	private $ccache;

	function __construct($link, $ccache) {
		$this->link = $link;
		$this->ccache = $ccache;
	}

	function get_all_counters($omode = "flc", $active_feed = false) {

		if (!$omode) $omode = "flc";

		$data = $this->get_global_counters();

		$data = array_merge($data, $this->get_virt_counters());

		if (strchr($omode, "l")) $data = array_merge($data, $this->get_label_counters());
		if (strchr($omode, "f")) $data = array_merge($data, $this->get_feed_counters($active_feed));
		if (strchr($omode, "t")) $data = array_merge($data, $this->get_tag_counters());
		if (strchr($omode, "c")) $data = array_merge($data, $this->get_category_counters());

		return $data;
	}

	function get_category_counters() {
		$ret_arr = array();

		/* Labels category */

		$cv = array("id" => -2, "kind" => "cat",
				"counter" => $this->get_category_unread(-2));

		array_push($ret_arr, $cv);

		$age_qpart = $this->get_max_age_subquery();

		$result = db_query($this->link, "SELECT id AS cat_id, value AS unread
				FROM ttrss_feed_categories, ttrss_cat_counters_cache
				WHERE ttrss_cat_counters_cache.feed_id = id AND
				ttrss_feed_categories.owner_uid = " . $_SESSION["uid"]);

		while ($line = db_fetch_assoc($result)) {
			$line["cat_id"] = (int) $line["cat_id"];

			$cv = array("id" => $line["cat_id"], "kind" => "cat",
					"counter" => $line["unread"]);

			array_push($ret_arr, $cv);
		}

		/* Special case: NULL category doesn't actually exist in the DB */

		$cv = array("id" => 0, "kind" => "cat",
				"counter" => $this->ccache->find(0, $_SESSION["uid"], true));

		array_push($ret_arr, $cv);

		return $ret_arr;
	}

	function get_feed_counters($active_feed = false) {

		$ret_arr = array();

		$age_qpart = $this->get_max_age_subquery();

		$query = "SELECT ttrss_feeds.id,
					ttrss_feeds.title,
					".SUBSTRING_FOR_DATE."(ttrss_feeds.last_updated,1,19) AS last_updated,
					last_error, value AS count
				FROM ttrss_feeds, ttrss_counters_cache
				WHERE ttrss_feeds.owner_uid = ".$_SESSION["uid"]."
					AND ttrss_counters_cache.feed_id = id";

		$result = db_query($this->link, $query);
		$fctrs_modified = false;

		while ($line = db_fetch_assoc($result)) {

			$id = $line["id"];
			$count = $line["count"];
			$last_error = htmlspecialchars($line["last_error"]);
			$last_updated = make_local_datetime($this->link, $line['last_updated'], false);
			$has_img = feed_has_icon($id);

			if (date('Y') - date('Y', strtotime($line['last_updated'])) > 2)
			$last_updated = '';

			$cv = array("id" => $id,
					"updated" => $last_updated,
					"counter" => $count,
					"has_img" => (int) $has_img);

			if ($last_error) $cv["error"] = $last_error;

			if ($active_feed && $id == $active_feed) $cv["title"] = truncate_string($line["title"], 30);

			array_push($ret_arr, $cv);

		}

		return $ret_arr;
	}

	function get_global_counters($global_unread = -1) {
		$ret_arr = array();

		if ($global_unread == -1) {
			$global_unread = $this->get_global_unread();
		}

		$cv = array("id" => "global-unread", "counter" => $global_unread);

		array_push($ret_arr, $cv);

		$result = db_query($this->link, "SELECT COUNT(id) AS fn FROM
				ttrss_feeds WHERE owner_uid = " . $_SESSION["uid"]);

		$subscribed_feeds = db_fetch_result($result, 0, "fn");

		$cv = array("id" => "subscribed-feeds", "counter" => $subscribed_feeds);

		array_push($ret_arr, $cv);

		return $ret_arr;
	}

	public function get_tag_counters() {

		$ret_arr = array();

		$age_qpart = $this->get_max_age_subquery();

		$result = db_query($this->link, "SELECT tag_name,SUM((SELECT COUNT(int_id)
				FROM ttrss_user_entries,ttrss_entries WHERE int_id = post_int_id
					AND ref_id = id AND $age_qpart
					AND unread = true)) AS count FROM ttrss_tags
					WHERE owner_uid = ".$_SESSION['uid']." GROUP BY tag_name
					ORDER BY count DESC LIMIT 55");

		$tags = array();

		while ($line = db_fetch_assoc($result)) {
			$tags[$line["tag_name"]] += $line["count"];
		}

		foreach (array_keys($tags) as $tag) {
			$unread = $tags[$tag];
			$tag = htmlspecialchars($tag);

			$cv = array("id" => $tag,
					"kind" => "tag",
					"counter" => $unread);

			array_push($ret_arr, $cv);
		}

		return $ret_arr;
	}

	public function get_virt_counters() {

		$ret_arr = array();

		for ($i = 0; $i >= -4; $i--) {

			$count = $this->get_feed_unread($i);

			$cv = array("id" => $i, "counter" => $count);

			array_push($ret_arr, $cv);
		}

		return $ret_arr;
	}

	public function get_label_counters($descriptions = false) {

		$ret_arr = array();

		$age_qpart = $this->get_max_age_subquery();

		$owner_uid = $_SESSION["uid"];

		$result = db_query($this->link, "SELECT id, caption FROM ttrss_labels2
				WHERE owner_uid = '$owner_uid'");

		while ($line = db_fetch_assoc($result)) {

			$id = -$line["id"] - 11;

			$label_name = $line["caption"];
			$count = $this->get_feed_unread($id);

			$cv = array("id" => $id, "counter" => $count);

			if ($descriptions) $cv["description"] = $label_name;

			array_push($ret_arr, $cv);
		}

		return $ret_arr;
	}

	function get_category_unread($cat, $owner_uid = false) {

		if (!$owner_uid) $owner_uid = $_SESSION["uid"];

		if ($cat >= 0) {

			if ($cat != 0) {
				$cat_query = "cat_id = '$cat'";
			} else {
				$cat_query = "cat_id IS NULL";
			}

			$age_qpart = $this->get_max_age_subquery();

			$result = db_query($this->link, "SELECT id FROM ttrss_feeds WHERE $cat_query
						AND owner_uid = " . $owner_uid);

			$cat_feeds = array();
			while ($line = db_fetch_assoc($result)) {
				array_push($cat_feeds, "feed_id = " . $line["id"]);
			}

			if (count($cat_feeds) == 0) return 0;

			$match_part = implode(" OR ", $cat_feeds);

			$result = db_query($this->link, "SELECT COUNT(int_id) AS unread
					FROM ttrss_user_entries,ttrss_entries
					WHERE	unread = true AND ($match_part) AND id = ref_id
					AND $age_qpart AND owner_uid = " . $owner_uid);

			$unread = 0;

			# this needs to be rewritten
			while ($line = db_fetch_assoc($result)) {
				$unread += $line["unread"];
			}

			return $unread;
		} else if ($cat == -1) {
			return $this->get_feed_unread(-1) + $this->get_feed_unread(-2) +
						$this->get_feed_unread(-3) + $this->get_feed_unread(0);
		} else if ($cat == -2) {

			$result = db_query($this->link, "
					SELECT COUNT(unread) AS unread FROM
						ttrss_user_entries, ttrss_labels2, ttrss_user_labels2, ttrss_feeds
					WHERE label_id = ttrss_labels2.id AND article_id = ref_id AND
						ttrss_labels2.owner_uid = '$owner_uid'
						AND unread = true AND feed_id = ttrss_feeds.id
						AND ttrss_user_entries.owner_uid = '$owner_uid'");

			$unread = db_fetch_result($result, 0, "unread");

			return $unread;

		}
	}

	public function get_feed_articles($feed, $is_cat = false, $unread_only = false, $owner_uid = false) {

		$n_feed = (int) $feed;

		if (!$owner_uid) $owner_uid = $_SESSION["uid"];

		if ($unread_only) {
			$unread_qpart = "unread = true";
		} else {
			$unread_qpart = "true";
		}

		$age_qpart = $this->get_max_age_subquery();

		if ($is_cat) {
			return $this->get_category_unread($n_feed, $owner_uid);
		} if ($feed != "0" && $n_feed == 0) {

			$feed = db_escape_string($feed);

			$result = db_query($this->link, "SELECT SUM((SELECT COUNT(int_id)
					FROM ttrss_user_entries,ttrss_entries WHERE int_id = post_int_id
						AND ref_id = id AND $age_qpart
						AND $unread_qpart)) AS count FROM ttrss_tags
					WHERE owner_uid = $owner_uid AND tag_name = '$feed'");
			return db_fetch_result($result, 0, "count");

		} else if ($n_feed == -1) {
			$match_part = "marked = true";
		} else if ($n_feed == -2) {
			$match_part = "published = true";
		} else if ($n_feed == -3) {
			$match_part = "unread = true AND score >= 0";

			$intl = get_pref($this->link, "FRESH_ARTICLE_MAX_AGE", $owner_uid);

			if (DB_TYPE == "pgsql") {
				$match_part .= " AND updated > NOW() - INTERVAL '$intl hour' ";
			} else {
				$match_part .= " AND updated > DATE_SUB(NOW(), INTERVAL $intl HOUR) ";
			}
		} else if ($n_feed == -4) {
			$match_part = "true";
		} else if ($n_feed >= 0) {

			if ($n_feed != 0) {
				$match_part = "feed_id = '$n_feed'";
			} else {
				$match_part = "feed_id IS NULL";
			}

		} else if ($feed < -10) {

			$label_id = -$feed - 11;

			return $this->get_label_unread($label_id, $owner_uid);

		}

		if ($match_part) {

			if ($n_feed != 0) {
				$from_qpart = "ttrss_user_entries,ttrss_feeds,ttrss_entries";
				$feeds_qpart = "ttrss_user_entries.feed_id = ttrss_feeds.id AND";
			} else {
				$from_qpart = "ttrss_user_entries,ttrss_entries";
				$feeds_qpart = '';
			}

			$query = "SELECT count(int_id) AS unread
					FROM $from_qpart WHERE
					ttrss_user_entries.ref_id = ttrss_entries.id AND
			$age_qpart AND
			$feeds_qpart
			$unread_qpart AND ($match_part) AND ttrss_user_entries.owner_uid = $owner_uid";

			$result = db_query($this->link, $query);

		} else {

			$result = db_query($this->link, "SELECT COUNT(post_int_id) AS unread
					FROM ttrss_tags,ttrss_user_entries,ttrss_entries
					WHERE tag_name = '$feed' AND post_int_id = int_id AND ref_id = ttrss_entries.id
					AND $unread_qpart AND $age_qpart AND
						ttrss_tags.owner_uid = " . $owner_uid);
		}

		$unread = db_fetch_result($result, 0, "unread");

		return $unread;
	}

	function get_global_unread($user_id = false) {

		if (!$user_id) {
			$user_id = $_SESSION["uid"];
		}

		$result = db_query($this->link, "SELECT SUM(value) AS c_id FROM ttrss_counters_cache
				WHERE owner_uid = '$user_id' AND feed_id > 0");

		$c_id = db_fetch_result($result, 0, "c_id");

		return $c_id;
	}

	public static function get_max_age_subquery($days = COUNTERS_MAX_AGE) {
		if (DB_TYPE == "pgsql") {
			return "ttrss_entries.date_updated >
					NOW() - INTERVAL '$days days'";
		} else {
			return "ttrss_entries.date_updated >
					DATE_SUB(NOW(), INTERVAL $days DAY)";
		}
	}

	function get_feed_unread($feed, $is_cat = false) {
		return $this->get_feed_articles($feed, $is_cat, true, $_SESSION["uid"]);
	}

	function get_label_unread($label_id, $owner_uid = false) {
		if (!$owner_uid) $owner_uid = $_SESSION["uid"];

		$result = db_query($this->link, "
			SELECT COUNT(unread) AS unread FROM
							ttrss_user_entries, ttrss_labels2, ttrss_user_labels2, ttrss_feeds
						WHERE label_id = ttrss_labels2.id AND article_id = ref_id AND
							ttrss_labels2.owner_uid = '$owner_uid' AND ttrss_labels2.id = '$label_id'
			AND unread = true AND feed_id = ttrss_feeds.id
							AND ttrss_user_entries.owner_uid = '$owner_uid'");

		if (db_num_rows($result) != 0) {
			return db_fetch_result($result, 0, "unread");
		} else {
			return 0;
		}
	}



}
?>