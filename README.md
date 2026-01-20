# Multi-Tenant Booking System (多租戶預約系統)

這是一個功能完整的線上預約系統，採用 **多租戶架構設計**，讓每個租戶（例如：診所、美容院、健身房、補習班）都能擁有自己獨立的品牌頁面、服務項目和預約管理功能。

系統前端採用 **Livewire 3** 打造，提供流暢、動態的單頁應用（SPA）體驗；後端則由強大的 **Filament Admin Panel** 驅動，讓管理員可以輕鬆管理整個平台。

---

## 🎯 專案目標 (Project Goals)

- **多租戶隔離**：確保每個租戶的資料獨立、安全，避免跨租戶存取。
- **角色與權限管理**：支援超級管理員、租戶管理員、櫃台、教練/醫師等角色。
- **流暢的預約流程**：提供直覺的日曆排程與多步驟預約介面。
- **即時數據分析**：透過 Widget 與報表，顯示預約數、取消率、營收。
- **擴展性**：支援 API、支付整合、白標化設定，方便未來擴充。
- **良好使用者體驗**：響應式設計，支援暗色模式與行動裝置。

---

## ✨ 主要特色 (Key Features)

- **多租戶架構 (Multi-Tenancy)**
    - 租戶獨立子網域或路徑 (`/tenant-slug`)。
    - 租戶可自訂品牌顏色、Logo、歡迎訊息。
    - 資料完全隔離，透過 `tenant_id` 控制。

- **線上預約流程 (Booking Flow)**
    - 租戶服務列表 → 預約日曆 → 填寫資料 → 確認。
    - 支援拖拉排程，避免重複預約。
    - 預約成功後，自動建立客戶帳號。

- **管理後台 (Admin Panel)**
    - 基於 [Filament](https://filamentphp.com/)，介面美觀且功能強大。
    - **超級管理員**：管理所有租戶、查看全域統計。
    - **租戶管理員**：管理自己的服務、預約、客戶。
    - 內建儀表板：顯示預約數、營收、取消率。
    - 使用 `filament-shield` 進行精細的角色與權限管理。

- **響應式設計 (Responsive Design)**
    - 使用 [Tailwind CSS](https://tailwindcss.com/)，桌面與行動裝置皆有良好體驗。
    - 支援暗色模式。

---

## 🏗️ 系統架構亮點 (Architecture Highlights)

- **多租戶實現**  
  採用 **單一資料庫、以 `tenant_id` 區分** 的策略，透過 Laravel Global Scopes 確保資料隔離。

- **Livewire 元件化**  
  前端預約流程拆分為多個 Livewire 元件：
    - `TenantBookingHome`: 租戶服務列表首頁。
    - `BookingStepOne`: 預約日曆與時段選擇。
    - `BookingCalendar`: 核心日曆元件，計算可用時段並顯示。
    - `BookingStepTwo`: 填寫客戶資料。
    - `BookingConfirmation`: 確認與完成頁面。

- **權限管理**  
  使用 `filament-shield` 與 `spatie/permission`，支援多角色、多層級權限。

- **支付整合 (Future)**  
  預留 LINE Pay / 綠界 API 整合，支援訂金與支付流程。

---

## 🛠️ 技術棧 (Tech Stack)

- **後端 (Backend)**: Laravel 11
- **前端 (Frontend)**: Livewire 3, Alpine.js, Tailwind CSS
- **管理後台 (Admin Panel)**: Filament 3
- **資料庫 (Database)**: SQLite / MySQL / PostgreSQL
- **權限管理 (Permissions)**: BezhanSalleh/FilamentShield

---

## ✨ 程式碼亮點 (Code Highlights)

### 1. 優雅的多租戶資料隔離 (Global Scope)

為了確保租戶資料的絕對隔離，我們利用了 Laravel 的 `Global Scope`。只需在 Model 中加入一個 Trait，所有查詢就會自動加上 `where('tenant_id', ...)` 的條件，無需在每個地方手動編寫，大大提高了程式碼的簡潔性和安全性。

**`app/Models/Traits/BelongsToTenant.php`**

```php
has('tenant_id')) {
                $model->tenant_id = session('tenant_id');
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

**在 Model 中使用:**
只需要在屬於租戶的 Model (例如：`Service`, `Appointment`) 中使用這個 Trait 即可。

```php
use App\Models\Traits\BelongsToTenant;

class Service extends Model
{
    use HasFactory, BelongsToTenant;
    // ...
}
```

### 2. Livewire 即時預約時段計算

在預約日曆中，當使用者選擇不同日期時，前端需要即時更新可預約的時段列表。Livewire 讓這一切變得非常簡單，`updatedSelectedDate` Hook 會在前端日期變更時自動觸發，重新執行 `loadSlots` 方法，並將新的可用時段渲染到畫面上，全程無需刷新頁面。

**`app/Livewire/BookingCalendar.php`**

```php
class BookingCalendar extends Component
{
    public $selectedDate;
    public $availableSlots = [];

    // ...

    // 當 $selectedDate 屬性被前端更新時，這個方法會自動執行
    public function updatedSelectedDate()
    {
        $this->loadSlots();
    }

    public function loadSlots()
    {
        // 1. 定義所有可能的時段
        $allSlots = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

        // 2. 查詢當天已被預約的時段
        $bookedSlots = Appointment::where('tenant_id', $this->tenantId)
            ->whereDate('start_time', $this->selectedDate)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('start_time')
            ->map(fn($t) => $t->format('H:i'))
            ->toArray();

        // 3. 計算出真正可用的時段
        $this->availableSlots = array_diff($allSlots, $bookedSlots);
    }

    // ...
}
```

### 3. Filament 快速定義後台資源

Filament 讓我們能用非常宣告式 (declarative) 的語法來定義後台的表單和表格，大幅提升開發效率。以下是 `ServiceResource` 的部分程式碼，可以看到定義一個包含圖片上傳、文字輸入、關聯選擇的表單是多麼直觀。

**`app/Filament/Resources/ServiceResource.php`**

```php
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class ServiceResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('duration_minutes')
                    ->required()
                    ->numeric()
                    ->suffix('分鐘'),
                FileUpload::make('image_path')
                    ->image()
                    ->columnSpanFull(),
            ]);
    }
    // ...
}
```

---

## 🚀 安裝與設定指南 (Installation Guide)

1. Clone 專案

    ```bash
    git clone https://github.com/BpsEason/MultiTenant-Booking.git
    cd MultiTenant-Booking
    ```

2. 安裝 PHP 依賴

    ```bash
    composer install
    ```

3. 安裝前端依賴

    ```bash
    npm install
    npm run dev
    ```

4. 環境設定

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5. 資料庫遷移與填充

    ```bash
    php artisan migrate --seed
    ```

6. 建立儲存連結

    ```bash
    php artisan storage:link
    ```

7. 建立管理員帳號

    ```bash
    php artisan db:seed --class=TestBusinessSeeder
    ```

8. 啟動伺服器

    ```bash
    php artisan serve
    ```

    - 後台登入路徑: `/admin`

---

## 📊 開發路線圖 (Roadmap)

- **MVP**
    - 多租戶 CRUD
    - 預約流程（選時段 → 填資料 → 確認）
    - 基本儀表板統計

- **第二階段**
    - API 提供前端或第三方整合
    - 日曆拖拉排程
    - 租戶品牌化設定

- **第三階段**
    - 支付整合（LINE Pay / 綠界）
    - 高級報表分析（營收、活躍度、取消率）
    - 租戶通知系統（Email / LINE Bot）

---

## 📌 使用情境 (Use Cases)

- **診所**：病患線上預約看診，醫師查看個人課表。
- **美容院**：顧客預約美容服務，櫃台管理每日排程。
- **健身房**：會員預約課程，教練查看自己的課表。
- **補習班**：學生預約課程，管理員追蹤出席率。

---

# 📌 面試常見問答 (Q&A)

### 1. **請介紹一下這個專案的核心目標？**

**答：**  
這是一個多租戶線上預約系統，目標是讓不同租戶（診所、美容院、健身房等）能擁有獨立的品牌頁面與預約管理功能。系統提供完整的預約流程、角色與權限管理、即時數據分析，並支援 API 與支付整合，確保擴展性與安全性。

---

### 2. **為什麼選擇多租戶架構？**

**答：**  
多租戶架構能讓系統以單一程式碼基底服務多個租戶，降低維護成本。透過 `tenant_id` 做資料隔離，確保不同租戶的資料安全。這樣的設計也方便快速擴展，支援白標化與品牌自訂。

---

### 3. **你是如何處理資料隔離的？**

**答：**  
採用 **Column-based Multi-tenancy**，在每張表加上 `tenant_id` 欄位，並透過 Laravel Global Scope 確保查詢時自動過濾。這樣能避免跨租戶存取，並保持效能與簡單性。

---

### 4. **如何避免同一時段被重複預約？**

**答：**  
在資料庫層級設計唯一索引，例如 `(tenant_id, staff_id, start_time)`，並在程式邏輯中加上鎖機制，確保同一時段不會被重複預約。

---

### 5. **為什麼選擇 Filament 作為後台？**

**答：**  
Filament 提供快速生成 CRUD、Widget、統計圖表的能力，介面美觀且支援角色與權限管理。對於 SaaS 管理後台來說，它能大幅縮短開發時間，同時保持可擴展性。

---

### 6. **系統的角色與權限是如何設計的？**

**答：**  
使用 `spatie/permission` 搭配 `filament-shield`，支援多角色：

- 超級管理員：管理所有租戶與全域統計。
- 租戶管理員：管理自己的服務、預約、客戶。
- 櫃台：處理預約與客戶報到。
- 醫師/教練：查看個人課表。

---

### 7. **系統如何支援擴展性？**

**答：**

- 提供 REST/GraphQL API，方便前端或第三方整合。
- 預留支付整合（LINE Pay / 綠界）。
- 租戶可自訂品牌設定（Logo、顏色）。
- 模組化設計，前端 Livewire 元件化，後端 Filament Resource 化。

---

### 8. **你如何確保使用者體驗？**

**答：**

- 預約流程設計成多步驟（選時段 → 填資料 → 確認），降低操作複雜度。
- 使用 Tailwind CSS 打造響應式設計，支援桌面與行動裝置。
- 支援暗色模式，提升使用者舒適度。

---

### 9. **如果要擴展到國際市場，你會怎麼做？**

**答：**

- 加入多語系支援（Laravel Localization）。
- 支援多貨幣支付。
- 調整租戶設定，讓不同國家租戶能自訂時區與營業時間。

---

### 10. **這個專案最大的挑戰是什麼？**

**答：**  
最大的挑戰是 **多租戶資料隔離與權限管理**。必須確保每個租戶的資料安全，同時提供彈性的角色權限。另一個挑戰是 **預約併發控制**，需要在高流量下避免重複預約。
