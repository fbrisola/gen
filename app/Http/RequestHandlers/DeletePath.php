<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2021 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees\Http\RequestHandlers;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use League\Flysystem\Util;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function e;
use function in_array;
use function response;

/**
 * Delete a file or folder from the data filesystem.
 */
class DeletePath implements RequestHandlerInterface
{
    private const PROTECTED_PATHS = [
        'config.ini.php',
        'index.php',
        '.htaccess',
    ];

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data_filesystem = Registry::filesystem()->data();

        $path = $request->getQueryParams()['path'] ?? '';

        $normalized_path = Util::normalizePath($path);

        if (in_array($normalized_path, self::PROTECTED_PATHS, true)) {
            FlashMessages::addMessage(I18N::translate('The file %s could not be deleted.', e($path)), 'danger');

            return response();
        }

        if ($data_filesystem->has($normalized_path)) {
            $metadata = $data_filesystem->getMetadata($normalized_path);

            switch ($metadata['type']) {
                case 'file':
                    try {
                        $data_filesystem->delete($normalized_path);
                        FlashMessages::addMessage(I18N::translate('The file %s has been deleted.', e($path)), 'success');
                    } catch (Throwable $ex) {
                        FlashMessages::addMessage(I18N::translate('The file %s could not be deleted.', e($path)), 'danger');
                    }
                    break;

                case 'dir':
                    try {
                        $data_filesystem->deleteDir($normalized_path);
                        FlashMessages::addMessage(I18N::translate('The folder %s has been deleted.', e($path)), 'success');
                    } catch (Throwable $ex) {
                        FlashMessages::addMessage(I18N::translate('The folder %s could not be deleted.', e($path)), 'danger');
                    }
                    break;
            }
        } else {
            FlashMessages::addMessage(I18N::translate('The file %s could not be deleted.', e($path)), 'danger');
        }

        return response();
    }
}
