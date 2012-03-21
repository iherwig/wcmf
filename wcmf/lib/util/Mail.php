<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace wcmf\lib\util;

/**
 * @class Mail
 * @brief Class to send mails.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Mail {

  /**
   * Send an email.
   * @param fromName The senders name
   * @param fromEmail The senders email adress
   * @param toEmail The recipients email adress
   * @param subject The subject of the email
   * @param body The body of the email
   * @return True/False wether the mail was successfully sent.
   */
  public static function send($fromName, $fromEmail, $toEmail, $subject, $body) {
    // replace ":", ";", "," in $fromName for use in "From" and "Reply-To"
    $email_header = str_replace(array(':', ';', ','), ' ', $fromName);

    $headers = "";
    $headers .= "From: ".$email_header."<".$fromEmail.">\n";
    $headers .= "X-Sender: <".$email_header.">\n";
    $headers .= "X-Mailer: PHP\n";
    $headers .= "X-Priority: 3\n";
    $headers .= "Reply-To: ".$email_header."<".$fromEmail.">\n";
    $headers .= "Content-type: text/plain; charset=iso-8859-1\n";

    return mail($toEmail, $subject, $body, $headers);
  }
}
?>