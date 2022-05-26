<?php

namespace KimaiPlugin\SchenkExporterBundle\Controller;

use App\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;

/**
 * @Route(path="/schenkexporter/{kw}:{jahr}")
 */
final class SchenkExporterController extends AbstractController
{
    /**
     * @Route(path="", name="schenkexporter", methods={"GET"})
     * 
     * @param mixed $name
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function schenkexporter(string $kw, string $jahr): Response
    {
        $user = $this->getUser();
        $user = str_replace(' ', '.', $user);

        $urlToGet =  "http://schenkexporter:5000/$user:$kw:$jahr";
        $fileName = strtoupper(explode('.', $user)[0][0]).strtoupper(explode('.', $user)[1][0]).'_KW'.$kw.'_'.$jahr[2].$jahr[3].'.xlsx';
        $ch = curl_init($urlToGet);  

        $fp = fopen($fileName, 'wb');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        curl_exec($ch);

        if (curl_errno($ch)){
            $error_msg = curl_errno($ch);
        }
        curl_close($ch);

        fclose($fp);

        if(isset($error_msg)){
            throw new \Exception('Something went wrong!');
        }
        else{
            $response = new BinaryFileResponse($fileName);
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
            if($mimeTypeGuesser->isSupported()){
                // Guess the mimetype of the file according to the extension of the file
                $response->headers->set('Content-Type', $mimeTypeGuesser->guess($fileName));
            }else{
                // Set the mimetype of the file manually, in this case for a text file is text/plain
                $response->headers->set('Content-Type', 'text/plain');
            }
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName
            );
            $response->deleteFileAfterSend(true);
        }
        return $response;
    }
}