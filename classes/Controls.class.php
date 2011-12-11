<?php
class Controls {
	public static function print_feed_cat_select($link, $id, $default_id = "", $attributes = "", $include_all_cats = true) {

		print "<select id=\"$id\" name=\"$id\" default=\"$default_id\" onchange=\"catSelectOnChange(this)\" $attributes>";

		if ($include_all_cats) {
			print "<option value=\"0\">".__('Uncategorized')."</option>";
		}

		$result = db_query($link, "SELECT id,title FROM ttrss_feed_categories
				WHERE owner_uid = ".$_SESSION["uid"]." ORDER BY title");

		if (db_num_rows($result) > 0 && $include_all_cats) {
			print "<option disabled=\"1\">--------</option>";
		}

		while ($line = db_fetch_assoc($result)) {
			if ($line["id"] == $default_id) {
				$is_selected = "selected=\"1\"";
			} else {
				$is_selected = "";
			}

			if ($line["title"])
			printf("<option $is_selected value='%d'>%s</option>",
			$line["id"], htmlspecialchars($line["title"]));
		}

		#		print "<option value=\"ADD_CAT\">" .__("Add category...") . "</option>";

		print "</select>";
	}

	public static function print_feed_select($link, $id, $default_id = "", $attributes = "", $include_all_feeds = true) {

		print "<select id=\"$id\" name=\"$id\" $attributes>";
		if ($include_all_feeds) {
			print "<option value=\"0\">".__('All feeds')."</option>";
		}

		$result = db_query($link, "SELECT id,title FROM ttrss_feeds
				WHERE owner_uid = ".$_SESSION["uid"]." ORDER BY title");

		if (db_num_rows($result) > 0 && $include_all_feeds) {
			print "<option disabled>--------</option>";
		}

		while ($line = db_fetch_assoc($result)) {
			if ($line["id"] == $default_id) {
				$is_selected = "selected=\"1\"";
			} else {
				$is_selected = "";
			}

			$title = truncate_string(htmlspecialchars($line["title"]), 40);

			printf("<option $is_selected value='%d'>%s</option>",
			$line["id"], $title);
		}

		print "</select>";
	}

	public static function print_label_select($link, $name, $value, $attributes = "") {

		$result = db_query($link, "SELECT caption FROM ttrss_labels2
				WHERE owner_uid = '".$_SESSION["uid"]."' ORDER BY caption");

		print "<select default=\"$value\" name=\"" . htmlspecialchars($name) . "\" $attributes onchange=\"labelSelectOnChange(this)\" >";

		while ($line = db_fetch_assoc($result)) {

			$issel = ($line["caption"] == $value) ? "selected=\"1\"" : "";

			print "<option value=\"".htmlspecialchars($line["caption"])."\"$issel>" .
						htmlspecialchars($line["caption"]) . "</option>";

		}

		print "</select>";


	}

	public static function print_select($id, $default, $values, $attributes = "") {
		print "<select name=\"$id\" id=\"$id\" $attributes>";
		foreach ($values as $v) {
			if ($v == $default)
			$sel = "selected=\"1\"";
			else
			$sel = "";

			print "<option value=\"$v\" $sel>$v</option>";
		}
		print "</select>";
	}

	public static function print_select_hash($id, $default, $values, $attributes = "") {
		print "<select name=\"$id\" id='$id' $attributes>";
		foreach (array_keys($values) as $v) {
			if ($v == $default)
			$sel = 'selected="selected"';
			else
			$sel = "";

			print "<option $sel value=\"$v\">".$values[$v]."</option>";
		}

		print "</select>";
	}

	public static function print_radio($id, $default, $true_is, $values, $attributes = "") {
		foreach ($values as $v) {

			if ($v == $default)
			$sel = "checked";
			else
			$sel = "";

			if ($v == $true_is) {
				$sel .= " value=\"1\"";
			} else {
				$sel .= " value=\"0\"";
			}

			print "<input class=\"noborder\" dojoType=\"dijit.form.RadioButton\"
					type=\"radio\" $sel $attributes name=\"$id\">&nbsp;$v&nbsp;";

		}
	}

}
?>