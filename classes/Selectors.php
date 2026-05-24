<?php

namespace Nautilus;

class Selector {

  // All Pages
  public static function all() {
    return ", include=all, status!=trash";
  }

  // Published
  public static function published() {
    $selector = ", status!=" . Page::statusUnpublished;
    $selector .= ", status!=" . Page::statusHidden;
    return $selector;
  }

  // Unpublished
  public static function unpublished() {
    return ", status=" . Page::statusUnpublished;
  }

  // Hidden
  public static function hidden() {
    return ", status=" . Page::statusHidden;
  }

  // Specified field is Empty
  public static function isEmpty($fieldName) {
    return ", $fieldName=''";
  }

  // Specified field is Not Empty
  public static function isNotEmpty($fieldName) {
    return ", $fieldName!=''";
  }

  // User - Pages created by this user
  public static function user($user) {
    return ", created_users_id=$user";
  }

  // --------------------------------------------------------- 
  // Date Range
  // --------------------------------------------------------- 

  // Period - Pages between these dates
  public static function period($startDate, $endDate, $key = "created") {
    return ", $key>=$startDate, $key<=$endDate";
  }

  // Month - Pages in this month
  public static function month($params) {
    $year = $params['year'] ?? date('Y');
    $month = $params['month'];
    $key = $params['key'] ?? "created";

    $start = strtotime("first day of $year-$month");
    $end = strtotime("last day of $year-$month 23:59:59");
    return ", $key>=$start, $key<=$end";
  }

  // Year - Pages in this year
  public static function year($params) {
    $year = $params['year'] ?? date('Y');
    $key = $params['key'] ?? "created";

    $start = strtotime("first day of January $year");
    $end = strtotime("last day of December $year 23:59:59");
    return ", $key>=$start, $key<=$end";
  }

  // Exact date - Pages on this date
  public static function date($date, $key = "created") {
    return ", $key=$date";
  }

  // Start Date - Pages after this date
  public static function startDate($date, $key = "created") {
    return ", $key>=$date";
  }

  // End Date - Pages before this date
  public static function endDate($date, $key = "created") {
    return ", $key<=$date";
  }

  // Before Date - Pages before this date
  public static function beforeDate($date, $key = "created") {
    return ", $key<=$date";
  }

  // After Date - Pages after this date
  public static function afterDate($date, $key = "created") {
    return ", $key>=$date";
  }

  // Last month - Pages in the last month
  public static function lastMonth($key = "created") {
    $selector = ", $key>=" . strtotime("first day of this month -1 month");
    $selector .= ", $key<=" . strtotime("last day of previous month");
    return $selector;
  }

  // This month - Pages in this month
  public static function thisMonth($key = "created") {
    $selector = ", $key>=" . strtotime("first day of this month");
    $selector .= ", $key<=" . strtotime("last day of this month");
    return $selector;
  }

  // Last Week - Pages in the last week
  public static function lastWeek($key = "created") {
    $selector = ", $key>=" . strtotime("last week");
    $selector .= ", $key<=" . strtotime("last week +1 week");
    return $selector;
  }

  // This Week - Pages in this week
  public static function thisWeek($key = "created") {
    $selector = ", $key>=" . strtotime("this week");
    $selector .= ", $key<=" . strtotime("this week +1 week");
    return $selector;
  }
}
