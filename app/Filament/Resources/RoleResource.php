<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages as ShieldPages;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RoleResource extends Resource implements HasShieldPermissions
{
    protected static ?string $recordTitleAttribute = 'name';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Select::make('panel_filter')
                                    ->label(__('لوحة التحكم'))
                                    ->options([
                                        'admin' => 'لوحة الإدارة (Admin)',
                                        'gis' => 'نظام المعلومات الجغرافية (GIS)',
                                        'estidama_panal' => 'إستدامة (Estidama)',
                                    ])
                                    ->default('admin')
                                    ->live()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default('web')
                                    ->nullable()
                                    ->maxLength(255),
                            ])
                            ->columns(2),
                    ]),
                static::getPanelPermissionsTabs(),
            ]);
    }

    protected static function getPanelPermissionsTabs(): Forms\Components\Component
    {
        return Forms\Components\Tabs::make('Permissions')
            ->contained()
            ->tabs([
                static::getResourcesTab(),
                static::getPagesTab(),
                static::getWidgetsTab(),
            ])
            ->columnSpanFull();
    }

    protected static function getResourcesTab(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('resources')
            ->label(__('filament-shield::filament-shield.resources'))
            ->schema(fn(callable $get) => static::getResourcesSchema($get('panel_filter')))
            ->visible(fn(): bool => Utils::isResourceEntityEnabled());
    }

    protected static function getPagesTab(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('pages')
            ->label(__('filament-shield::filament-shield.pages'))
            ->schema(fn(callable $get) => static::getPagesSchema($get('panel_filter')))
            ->visible(fn(): bool => Utils::isPageEntityEnabled());
    }

    protected static function getWidgetsTab(): Forms\Components\Component
    {
        return Forms\Components\Tabs\Tab::make('widgets')
            ->label(__('filament-shield::filament-shield.widgets'))
            ->schema(fn(callable $get) => static::getWidgetsSchema($get('panel_filter')))
            ->visible(fn(): bool => Utils::isWidgetEntityEnabled());
    }

    protected static function getResourcesSchema(?string $panelId): array
    {
        $entities = static::getFilteredResources($panelId);

        if (empty($entities)) {
            return [
                Forms\Components\Placeholder::make('no_resources')
                    ->label('')
                    ->content(__('لا توجد موارد متاحة لهذه اللوحة')),
            ];
        }

        return [
            Forms\Components\Grid::make()
                ->schema(
                    collect($entities)
                        ->map(function ($entity) {
                            return Forms\Components\Section::make(
                                static::getResourceLabel($entity)
                            )
                                ->compact()
                                ->schema([
                                    static::getCheckboxListForResource($entity),
                                ])
                                ->columnSpan(static::shield()->getSectionColumnSpan())
                                ->collapsible();
                        })
                        ->toArray()
                )
                ->columns(static::shield()->getGridColumns()),
        ];
    }

    protected static function getPagesSchema(?string $panelId): array
    {
        $options = static::getFilteredPageOptions($panelId);

        if (empty($options)) {
            return [
                Forms\Components\Placeholder::make('no_pages')
                    ->label('')
                    ->content(__('لا توجد صفحات متاحة لهذه اللوحة')),
            ];
        }

        return [
            static::getCheckboxListFormComponent('pages_tab', $options),
        ];
    }

    protected static function getWidgetsSchema(?string $panelId): array
    {
        $options = static::getFilteredWidgetOptions($panelId);

        if (empty($options)) {
            return [
                Forms\Components\Placeholder::make('no_widgets')
                    ->label('')
                    ->content(__('لا توجد ودجات متاحة لهذه اللوحة')),
            ];
        }

        return [
            static::getCheckboxListFormComponent('widgets_tab', $options),
        ];
    }

    protected static function getFilteredResources(?string $panelId): array
    {
        $panel = Filament::getPanel($panelId ?? 'admin');
        $resources = $panel->getResources();

        if (Utils::discoverAllResources()) {
            $all = [];
            foreach (Filament::getPanels() as $p) {
                $all = array_merge($all, $p->getResources());
            }
            $resources = array_unique($all);
        }

        return collect($resources)
            ->reject(function ($resource) {
                if (Utils::isGeneralExcludeEnabled()) {
                    return in_array(
                        Str::of($resource)->afterLast('\\'),
                        Utils::getExcludedResouces()
                    );
                }
                return false;
            })
            ->mapWithKeys(function ($resource) {
                $shield = FilamentShieldPlugin::get();
                $name = Str::of($resource)
                    ->afterLast('Resources\\')
                    ->beforeLast('Resource')
                    ->replace('\\', '')
                    ->snake()
                    ->replace('_', '::');
                return [
                    (string) $name => [
                        'resource' => (string) $name,
                        'model' => Str::of($resource::getModel())->afterLast('\\'),
                        'fqcn' => $resource,
                    ],
                ];
            })
            ->sortKeys()
            ->toArray();
    }

    protected static function getFilteredPageOptions(?string $panelId): array
    {
        $panel = Filament::getPanel($panelId ?? 'admin');
        $pages = $panel->getPages();

        if (Utils::discoverAllPages()) {
            $all = [];
            foreach (Filament::getPanels() as $p) {
                $all = array_merge($all, $p->getPages());
            }
            $pages = array_unique($all);
        }

        return collect($pages)
            ->reject(function ($page) {
                if (Utils::isGeneralExcludeEnabled()) {
                    return in_array(Str::afterLast($page, '\\'), Utils::getExcludedPages());
                }
                return false;
            })
            ->mapWithKeys(function ($page) {
                $permission = Str::of(class_basename($page))
                    ->prepend(Str::of(Utils::getPagePermissionPrefix())->append('_'));
                return [(string) $permission => (string) $permission];
            })
            ->toArray();
    }

    protected static function getFilteredWidgetOptions(?string $panelId): array
    {
        $panel = Filament::getPanel($panelId ?? 'admin');
        $widgets = $panel->getWidgets();

        if (Utils::discoverAllWidgets()) {
            $all = [];
            foreach (Filament::getPanels() as $p) {
                $all = array_merge($all, $p->getWidgets());
            }
            $widgets = array_unique($all);
        }

        return collect($widgets)
            ->reject(function ($widget) {
                if (Utils::isGeneralExcludeEnabled()) {
                    return in_array(
                        Str::of(class_basename($widget))->afterLast('\\'),
                        Utils::getExcludedWidgets()
                    );
                }
                return false;
            })
            ->mapWithKeys(function ($widget) {
                $permission = Str::of(class_basename($widget))
                    ->prepend(Str::of(Utils::getWidgetPermissionPrefix())->append('_'));
                return [(string) $permission => (string) $permission];
            })
            ->toArray();
    }

    protected static function getResourceLabel(array $entity): string
    {
        // dd($entity);
        $shield = static::shield();
        if ($shield->hasLocalizedPermissionLabels()) {
            return FilamentShield::getLocalizedResourceLabel($entity['fqcn']);
        }
        return (string) $entity['model'];
    }

    protected static function getCheckboxListForResource(array $entity): Forms\Components\Component
    {
        $prefixes = Utils::getResourcePermissionPrefixes($entity['fqcn']);
        $options = collect($prefixes)
            ->mapWithKeys(function ($permission) use ($entity) {
                $name = $permission . '_' . $entity['resource'];
                $label = static::shield()->hasLocalizedPermissionLabels()
                    ? FilamentShield::getLocalizedResourcePermissionLabel($permission)
                    : $name;
                return [$name => $label];
            })
            ->toArray();

        return static::getCheckboxListFormComponent($entity['resource'], $options);
    }

    protected static function getCheckboxListFormComponent(string $name, array $options): Forms\Components\Component
    {
        return Forms\Components\CheckboxList::make($name)
            ->label('')
            ->options($options)
            ->searchable()
            ->bulkToggleable()
            ->gridDirection('row')
            ->columns(static::shield()->getResourceCheckboxListColumns())
            ->columnSpan(static::shield()->getResourceCheckboxListColumnSpan())
            ->afterStateHydrated(function (Forms\Components\Component $component, string $operation, $record) use ($options) {
                if (in_array($operation, ['edit', 'view']) && $record) {
                    $state = collect($options)
                        ->filter(fn($val, $key) => $record->checkPermissionTo($key))
                        ->keys()
                        ->toArray();
                    $component->state($state);
                }
            })
            ->dehydrated(fn($state) => !blank($state));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('font-medium')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn($state): string => Str::headline($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success']),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
            'view' => Pages\ViewRole::route('/{record}'),
        ];
    }

    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.roles');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Utils::isResourceNavigationRegistered();
    }

    public static function getNavigationGroup(): ?string
    {
        return Utils::isResourceNavigationGroupEnabled()
            ? __('filament-shield::filament-shield.nav.group')
            : '';
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-shield::filament-shield.nav.role.label');
    }

    public static function getNavigationIcon(): string
    {
        return __('filament-shield::filament-shield.nav.role.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return Utils::getResourceNavigationSort();
    }

    public static function getSlug(): string
    {
        return Utils::getResourceSlug();
    }

    public static function getNavigationBadge(): ?string
    {
        return Utils::isResourceNavigationBadgeEnabled()
            ? strval(static::getEloquentQuery()->count())
            : null;
    }

    public static function shield(): FilamentShieldPlugin
    {
        return FilamentShieldPlugin::get();
    }
}
