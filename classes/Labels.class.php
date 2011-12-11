<?php
class Labels {
	private $link;		
	
	function __construct($link) {
		$this->link = $link;
	}
	
	function add_article($id, $label, $owner_uid) {
		$label_id = $this->find_id($label, $owner_uid);
	
		if (!$label_id) return;
	
		$result = db_query($this->link,
				"SELECT
					article_id FROM ttrss_labels2, ttrss_user_labels2
				WHERE
					label_id = id AND
					label_id = '$label_id' AND
					article_id = '$id' AND owner_uid = '$owner_uid'
				LIMIT 1");
	
		if (db_num_rows($result) == 0) {
			db_query($this->link, "INSERT INTO ttrss_user_labels2
					(label_id, article_id) VALUES ('$label_id', '$id')");
		}
	
		$this->clear_cache($id);
	
	}
	
	function remove($id, $owner_uid) {
		if (!$owner_uid) $owner_uid = $_SESSION["uid"];
	
		db_query($this->link, "BEGIN");
	
		$result = db_query($this->link, "SELECT caption FROM ttrss_labels2
				WHERE id = '$id'");
	
		$caption = db_fetch_result($result, 0, "caption");
	
		$result = db_query($this->link, "DELETE FROM ttrss_labels2 WHERE id = '$id'
				AND owner_uid = " . $owner_uid);
	
		if (db_affected_rows($this->link, $result) != 0 && $caption) {
	
			/* Remove access key for the label */
	
			$ext_id = -11 - $id;
	
			db_query($this->link, "DELETE FROM ttrss_access_keys WHERE
					feed_id = '$ext_id' AND owner_uid = $owner_uid");
	
			/* Disable filters that reference label being removed */
	
			db_query($this->link, "UPDATE ttrss_filters SET
					enabled = false WHERE action_param = '$caption'
						AND action_id = 7
						AND owner_uid = " . $owner_uid);
	
			/* Remove cached data */
	
			db_query($this->link, "UPDATE ttrss_user_entries SET label_cache = ''
					WHERE label_cache LIKE '%$caption%' AND owner_uid = " . $owner_uid);
	
		}
	
		db_query($this->link, "COMMIT");
	}
	
	function create($caption) {
	
		db_query($this->link, "BEGIN");
	
		$result = false;
	
		$result = db_query($this->link, "SELECT id FROM ttrss_labels2
				WHERE caption = '$caption' AND owner_uid =  ". $_SESSION["uid"]);
	
		if (db_num_rows($result) == 0) {
			$result = db_query($this->link,
					"INSERT INTO ttrss_labels2 (caption,owner_uid)
						VALUES ('$caption', '".$_SESSION["uid"]."')");
	
			$result = db_affected_rows($this->link, $result) != 0;
		}
	
		db_query($this->link, "COMMIT");
	
		return $result;
	}
	
	function clear_cache($id) {
	
		db_query($this->link, "UPDATE ttrss_user_entries SET
				label_cache = '' WHERE ref_id = '$id'");
	
	}
	
	function remove_article($id, $label, $owner_uid) {
	
		$label_id = $this->find_id($label, $owner_uid);
	
		if (!$label_id) return;
	
		$result = db_query($this->link,
				"DELETE FROM ttrss_user_labels2
				WHERE
					label_id = '$label_id' AND
					article_id = '$id'");
	
		$this->clear_cache($id);
	}
	
	function find_caption($label, $owner_uid) {
		$result = db_query($this->link,
				"SELECT caption FROM ttrss_labels2 WHERE id = '$label'
					AND owner_uid = '$owner_uid' LIMIT 1");
	
		if (db_num_rows($result) == 1) {
			return db_fetch_result($result, 0, "caption");
		} else {
			return "";
		}
	}
	
	function update_cache($id, $labels = false, $force = false) {
	
		if ($force)
			$this->clear_cache($id);
	
		if (!$labels)
			$labels = get_article_labels($this->link, $id);
	
		$labels = db_escape_string(json_encode($labels));
	
		db_query($this->link, "UPDATE ttrss_user_entries SET
				label_cache = '$labels' WHERE ref_id = '$id'");
	
	}
	
	function find_id($label, $owner_uid) {
		$result = db_query($this->link,
				"SELECT id FROM ttrss_labels2 WHERE caption = '$label'
					AND owner_uid = '$owner_uid' LIMIT 1");
	
		if (db_num_rows($result) == 1) {
			return db_fetch_result($result, 0, "id");
		} else {
			return 0;
		}
	}
	
	function get_article_labels($id) {
		$rv = array();
	
		$result = db_query($this->link, "SELECT label_cache FROM
				ttrss_user_entries WHERE ref_id = '$id' AND owner_uid = " .
		$_SESSION["uid"]);

		$label_cache = db_fetch_result($result, 0, "label_cache");

		if ($label_cache) {

			$label_cache = json_decode($label_cache, true);

			if ($label_cache["no-labels"] == 1)
				return $rv;
			else
				return $label_cache;
		}

		$result = db_query($this->link,
				"SELECT DISTINCT label_id,caption,fg_color,bg_color
					FROM ttrss_labels2, ttrss_user_labels2
				WHERE id = label_id
					AND article_id = '$id'
					AND owner_uid = ".$_SESSION["uid"] . "
				ORDER BY caption");

		while ($line = db_fetch_assoc($result)) {
			$rk = array($line["label_id"], $line["caption"], $line["fg_color"],
			$line["bg_color"]);
			array_push($rv, $rk);
		}
		if (count($rv) > 0)
			$this->update_cache($id, $rv);
		else
			$this->update_cache($id, array("no-labels" => 1));
	
		return $rv;
	}
	
	function format_article_labels($labels, $id) {
	
		$labels_str = "";
	
		foreach ($labels as $l) {
			$labels_str .= sprintf("<span class='hlLabelRef'
					style='color : %s; background-color : %s'>%s</span>",
			$l[2], $l[3], $l[1]);
		}
	
		return $labels_str;
	
	}
	
}
?>