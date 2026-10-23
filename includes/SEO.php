<?php
/**
 * SEO & Structured Data Generator
 */

class SEO {
    private $site;
    
    public function __construct($site) {
        $this->site = $site;
    }
    
    /**
     * Generate Article schema
     */
    public function article($article) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['judul'],
            'image' => [
                media_url($article['gambar'])
            ],
            'datePublished' => $article['tanggal'],
            'dateModified' => $article['diperbarui'] ?? $article['tanggal'],
            'author' => [
                '@type' => 'Person',
                'name' => $article['penulis']
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->site['site_name'],
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => media_url($this->site['logo'])
                ]
            ],
            'description' => mb_strimwidth(strip_tags($article['isi']), 0, 160, '...'),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url('berita-detail.php?slug=' . $article['slug'])
            ]
        ];
        
        return $this->render($schema);
    }
    
    /**
     * Generate Organization schema
     */
    public function organization() {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => $this->site['site_name'],
            'url' => url('index.php'),
            'logo' => media_url($this->site['logo']),
            'description' => $this->site['meta_description'] ?? '',
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'ID'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $this->site['telepon'],
                'email' => $this->site['email'],
                'contactType' => 'customer service'
            ],
            'sameAs' => array_filter([
                $this->site['facebook'] ?? null,
                $this->site['instagram'] ?? null,
                $this->site['youtube'] ?? null,
                $this->site['linkedin'] ?? null
            ])
        ];
        
        return $this->render($schema);
    }
    
    /**
     * Generate BreadcrumbList schema
     */
    public function breadcrumb($items) {
        $listItems = [];
        
        foreach ($items as $index => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url']
            ];
        }
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems
        ];
        
        return $this->render($schema);
    }
    
    /**
     * Render as JSON-LD script tag
     */
    private function render($schema) {
        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return '<script type="application/ld+json">' . $json . '</script>';
    }
}

// Helper
function seo() {
    global $S;
    static $instance = null;
    if ($instance === null) {
        $instance = new SEO($S);
    }
    return $instance;
}