<?php

return [
    /**
     * As a default NeoEloquent will not add created_at and updated_at timestamps to relationships.
     * As the maintainers of NeoEloquent we have found that timestamps on relationships are rarely used in practice,
     * and decided to preserve RAM by not set them unless the feature is explicitly enabled bellow.
     */
    'relationship-timestamps' => env('NEOELOQUENT_RELATIONSHIP_TIMESTAMPS',false)
];