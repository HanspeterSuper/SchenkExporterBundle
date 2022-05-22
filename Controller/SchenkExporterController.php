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
 * @Route(path="/schenkexporter/{mail}:{kw}:{jahr}")
 */
final class SchenkExporterController extends AbstractController
{
    /**
     * @Route(path="", name="schenkexporter", methods={"GET"})
     * 
     * @param mixed $name
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function schenkexporter(string $mail, string $kw, string $jahr): Response
    {
        $urlToGet =  "http://schenkexporter:5000/$mail:$kw:$jahr";
        $fileName = strtoupper(explode('.', explode('@', $mail)[0])[0][0]).strtoupper(explode('.', explode('@', $mail)[0])[1][0]).'_KW'.$kw.'_'.$jahr[2].$jahr[3].'.xlsx';
        $ch = curl_init($urlToGet);

        $dir = './tmp/';

        $files = glob( $dir . '*', GLOB_MARK ); 
        foreach( $files as $file ){ 
            if( substr( $file, -1 ) == '/' ) 
                delTree( $file ); 
            else 
                unlink( $file ); 
        } 
    
        if (is_dir($dir)) rmdir( $dir ); 

        mkdir($dir);

        $save_file_loc = $dir . $fileName;

        $fp = fopen($save_file_loc, 'wb');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);

        curl_close($ch);

        fclose($fp);

        $response = new BinaryFileResponse($dir.$fileName);
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if($mimeTypeGuesser->isSupported()){
            // Guess the mimetype of the file according to the extension of the file
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess($dir.$fileName));
        }else{
            // Set the mimetype of the file manually, in this case for a text file is text/plain
            $response->headers->set('Content-Type', 'text/plain');
        }
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        return $response;
    }
}