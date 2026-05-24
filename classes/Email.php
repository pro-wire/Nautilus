<?php

/**
 * Email Class
 * 
 * Provides email sending functionality with template support, attachments,
 * and dynamic content replacement.
 * 
 * Usage Examples:
 * 
 * // Basic email
 * $email = $nautilus->loadClass('Email');
 * 
 * $email->send([
 *   'to' => 'user@example.com',
 *   'from' => 'admin@example.com',
 *   'subject' => 'Welcome',
 *   'body' => '<h1>Welcome to our site!</h1>'
 * ]);
 * 
 * // Email with template file
 * $email->send([
 *   'to' => 'user@example.com',
 *   'from' => 'admin@example.com',
 *   'subject' => 'Welcome {user.name}',
 *   'email_template' => '/path/to/template.html',
 *   'related_page' => $userPage->id,
 *   'data' => ['user.name' => 'John Doe']
 * ]);
 * 
 * // Email with attachments
 * $email->send([
 *   'to' => 'user@example.com',
 *   'from' => 'admin@example.com',
 *   'subject' => 'Your Documents',
 *   'body' => 'Please find attached documents.',
 *   'attachments' => ['/path/to/file1.pdf', '/path/to/file2.pdf']
 * ]);
 * 
 */

namespace Nautilus;

require_once(__DIR__ . '/Strings.php');

use \ProcessWire\WireData;

class Email extends WireData {

  /**
   * Send email with various options
   * 
   * @param array $params Email parameters
   * @return bool True on success, false on failure
   */
  public function send($params = []) {

    $to = $params['to'] ?? "";
    $from = $params['from'] ?? "";
    $fromName = $params['fromName'] ?? "";
    $replyTo = $params['replyTo'] ?? "";
    $subject = $params['subject'] ?? "";
    $body = $params['body'] ?? "";
    $attachment = $params['attachment'] ?? "";
    $attachments = $params['attachments'] ?? [];
    $email_template = $params['email_template'] ?? "";

    $email_template_page = $params['email_template_page'] ?? "";
    $email_template_page = $email_template_page != "" ? $this->pages->get($email_template_page) : "";

    $data_array = $params['data'] ?? [];
    $related_page = $params['related_page'] ?? "";
    $related_page = $related_page != "" ? $this->pages->get("id=$related_page") : "";

    // Validate required fields
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
      $this->error("Valid Email To address is required");
      return false;
    }
    if (empty($from) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
      $this->error("Valid Email From address is required");
      return false;
    }
    if (empty($subject)) {
      $this->error("Email Subject is required");
      return false;
    }

    /**
     * If email template is provided
     * get the contents and use it as email body
     */
    if (!empty($email_template)) {
      if (file_exists($email_template)) {
        $body = file_get_contents($email_template);
      } else {
        $this->error("Email template file not found: " . $email_template);
        return false;
      }
    }

    /**
     * If email template page id is provided
     * get the page body field and use it as email body
     */
    if ($email_template_page != "" && $email_template_page->id) {
      $body = $email_template_page->body;
    }

    /**
     * If related page is provided
     * format the body string and replace {page.field} with the actual value
     */
    if ($related_page != "" && $related_page->id) {
      $subject = Strings::formatPageString($subject, $related_page);
      $body = Strings::formatPageString($body, $related_page);
    }

    /**
     * If data array is provided
     * replace {text} in a provided string with the key from a $data array
     */
    if (count($data_array) > 0) {
      $subject = Strings::replace($subject, $data_array);
      $body = Strings::replace($body, $data_array);
    }

    /**
     * Send email
     */
    try {
      $mail = \ProcessWire\wireMail();
      $mail->to($to);
      $mail->from($from);
      if (!empty($fromName)) $mail->fromName($fromName);
      if (!empty($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $mail->replyTo($replyTo);
      }
      $mail->subject($subject);
      $mail->bodyHTML($body);

      // single attachment
      if (!empty($attachment) && file_exists($attachment)) {
        $mail->attachment($attachment);
      }

      // multiple attachments
      if (count($attachments) > 0) {
        foreach ($attachments as $attachment) {
          if (file_exists($attachment)) {
            $mail->attachment($attachment);
          } else {
            $this->warning("Attachment file not found: " . $attachment);
          }
        }
      }

      $result = $mail->send();

      if ($result) {
        return true;
      } else {
        $this->error("Failed to send email");
        return false;
      }
    } catch (\Exception $e) {
      $this->error("Email sending failed: " . $e->getMessage());
      return false;
    }
  }
}
