<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

// This project ships no dev/tests/unit harness, so there is no lightweight, DB-free
// autoloader available for classes that only exist once code generation has produced
// them (e.g. PixlMods\Popup\Model\ResourceModel\Popup\CollectionFactory, or any core
// XFactory/Proxy/Interceptor a mock needs to be built from). Determining whether a
// given class name is a *virtual type* — required before Magento's code generator will
// touch it — needs the real, compiled DI config, which only a full application
// bootstrap provides.
//
// Booting the real application here (once per test run, before any test class loads)
// means these tests are not fully DB-independent — but it only ever *reads* already
// generated classes or generates missing ones on disk; it never touches application
// data. Once a class has been generated once, subsequent runs resolve it straight
// through Composer's autoloader and never exercise this path again.
require __DIR__ . '/../../../../../../app/bootstrap.php';

\Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
