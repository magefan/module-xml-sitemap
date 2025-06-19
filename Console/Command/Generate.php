<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\XmlSitemap\Console\Command;

use Magefan\XmlSitemap\Model\Config;
use Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory;
use Magento\Store\Model\App\Emulation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Escaper;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Helper\ProgressBar;

class Generate extends Command
{
    const SITEMAP_IDS_PARAM = 'ids';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Emulation
     */
    protected $appEmulation;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var State
     */
    private $state;

    /**
     * @param Config $config
     * @param CollectionFactory $collectionFactory
     * @param Emulation $appEmulation
     * @param Escaper $escaper
     * @param State $state
     * @param $name
     */
    public function __construct(
        Config $config,
        CollectionFactory $collectionFactory,
        Emulation $appEmulation,
        Escaper $escaper,
        State $state,
        $name = null
    ) {
        $this->config = $config;
        $this->collectionFactory = $collectionFactory;
        $this->appEmulation = $appEmulation;
        $this->escaper = $escaper;
        $this->state = $state;
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->config->isEnabled()) {
            try {
                $this->state->setAreaCode(Area::AREA_GLOBAL);
            } catch (LocalizedException $e) {
                $output->writeln((string)__('Something went wrong. %1', $this->escaper->escapeHtml($e->getMessage())));
            }

            $sitemapIDs = (string)$input->getOption(self::SITEMAP_IDS_PARAM);

            $sitemapIDs = $sitemapIDs
                ? array_map('intval', explode(',', $sitemapIDs))
                : [];

            $sitemapCollection = $this->collectionFactory->create();

            if ($sitemapIDs) {
                $output->writeln('<info>' . __('Provided IDs: %1', '`' . implode(',', $sitemapIDs) . '`') . '</info>');
                $sitemapCollection->addFieldToFilter('sitemap_id', ['in' => $sitemapIDs]);
            }

            if (!$sitemapCollection->getSize()) {
                if ($sitemapIDs) {
                    $output->writeln('<error>' . ((string)__('Can\'t find Site Map by provided ids.')) . '</error>');
                } else {
                    $output->writeln('<error>' . ((string)__('There is not Site Map to generate, please crate Site Map in Marketing -> SEO & Search -> Site Map.')) . '</error>');
                }

                return 0;
            }

            $progressBar = new ProgressBar($output, $sitemapCollection->getSize());

            $progressBar->start();
            $progressBar->display();

            foreach ($sitemapCollection as $sitemap) {
                try {
                    $this->appEmulation->startEnvironmentEmulation(
                        $sitemap->getStoreId(),
                        Area::AREA_FRONTEND,
                        true
                    );

                    $sitemap->generateXml();
                } catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                } finally {
                    $this->appEmulation->stopEnvironmentEmulation();
                    $progressBar->advance();
                }
            }

            $progressBar->finish();

            $output->writeln("\n");
            $output->writeln((string)__("Site Map(s) have been generated."));
            $output->writeln('');
        } else {
            $output->writeln("XmlSitemap extension is disabled. Please turn on it.");
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::SITEMAP_IDS_PARAM,
                null,
                InputOption::VALUE_OPTIONAL,
                'Site Map Ids'
            )
        ];

        $this->setDefinition($options);

        $this->setName("magefan:sitemap:generate");
        $this->setDescription("Generate Site Map(s) by IDs (comma separated)");

        parent::configure();
    }
}

