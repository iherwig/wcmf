<?php

convertToChronos("model.uml");

function convertToChronos($umlFilePath) {
	if (file_exists($umlFilePath)) {
		$content = file_get_contents($umlFilePath);
		// check for wcmf namespace
		if (preg_match('/ xmlns:wcmf=/', $content)) {
			file_put_contents($umlFilePath.".bak", $content);
			// replace namespaces
			$content = preg_replace('/xmlns:wcmf="http:\/\/\/schemas\/wcmf\/_lbV_UNRoEd2LOvE1lUIASw\/0"/',
					'xmlns:Chronos="http:///schemas/Chronos/_jAH0gM-nEd6fypAGO026Fw/0"', $content);
			$content = preg_replace('/xsi:schemaLocation="http:\/\/\/schemas\/wcmf\/_lbV_UNRoEd2LOvE1lUIASw\/0 .*?wcmf\.profile\.uml#_lbV_UtRoEd2LOvE1lUIASw"/',
					'xsi:schemaLocation="http:///schemas/Chronos/_jAH0gM-nEd6fypAGO026Fw/0 chronos.profile.uml#_jARlgc-nEd6fypAGO026Fw"', $content);
			$content = preg_replace('/href=".*?wcmf\.profile\.uml#_lbV_UtRoEd2LOvE1lUIASw"/',
					'href="chronos.profile.uml#_jARlgc-nEd6fypAGO026Fw"', $content);
			// replace stereotypes
			$content = preg_replace('/wcmf:WCMFController/', 'Chronos:ChiController', $content);
			$content = preg_replace('/wcmf:WCMFView/', 'Chronos:ChiView', $content);
			$content = preg_replace('/wcmf:WCMFActionKey/', 'Chronos:ChiActionKey', $content);
			$content = preg_replace('/wcmf:WCMFAssociation/', 'Chronos:ChiAssociation', $content);
			$content = preg_replace('/wcmf:WCMFNode/', 'Chronos:ChiNode', $content);
			$content = preg_replace('/wcmf:WCMFManyToMany/', 'Chronos:ChiManyToMany', $content);
			$content = preg_replace('/wcmf:WCMFValue/', 'Chronos:ChiValue', $content);
			$content = preg_replace('/wcmf:WCMFValueRef/', 'Chronos:ChiValueRef', $content);
			$content = preg_replace('/wcmf:WCMFSystem/', 'Chronos:ChiSystem', $content);
			file_put_contents($umlFilePath, $content);
		}
	}
}
?>