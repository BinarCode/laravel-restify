<?php

namespace Binaryk\LaravelRestify\Http\Requests;

/**
 * The index request of `POST /{repository}/apply-restify-advanced-filters`: the same listing as the
 * index, with the advanced filters read from the body instead of the base64 `filters` query parameter.
 * It extends the index request so everything that keys off one (indexQuery, fieldsForIndex, index
 * relationships) treats it as such.
 */
class RepositoryApplyFiltersRequest extends RepositoryIndexRequest {}
