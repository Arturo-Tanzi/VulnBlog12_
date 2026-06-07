<?php

namespace App\Livewire;

use illuminate\Support\Facades\Http;
use Livewire\Component;

class ArticleImagePreview extends Component
{
    public $imageUrl='';
    public $imageData='';
    public $isLoading=false;

    public function updateImageUrl()
    {
        $this->imageData='';
        $this->isLoading=false;

        if(!$this->imageUrl){
            return;
        }
        $this->isLoading=true;
        try {
            $url = $this->imageUrl;

            $parsed = parse_url($url);
            if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
                throw new \InvalidArgumentException("Invalid URL");
            }

            if (strtolower($parsed['scheme']) !== 'https') {
                throw new \InvalidArgumentException("Only HTTPS URLs are allowed");
            }
            $ip = gethostbyname($parsed['host']);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \InvalidArgumentException('Private/localhost addresses are not allowed');
            }

            $response = Http::timeout(5)
            ->withOptions([
                'allow_redirects' => false,
                'verify' => true,
                'max_redirects' => 0,
            ])
            ->get($url);

            $contentType = $response->header('Content-Type');
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array(strtolower($contentType), $allowedTypes, true)) {
                throw new \InvalidArgumentException("Invalid image type");
            }

            $body = $response->body();
            if (strlen($body) > 1024 * 1024) {
                throw new \InvalidArgumentException("Troppo grande");
            }

            $this->imageData = 'data:' . $contentType . ';base64,' . base64_encode($body);
        } catch (\Exception $e) {
            $this->addError('imageUrl', "Error loading image: " . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }
        
 }