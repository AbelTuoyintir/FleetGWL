<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Maintenance · Ghana Water Co.</title>
    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome (optional, used for small icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* paper & print-friendly */
        body {
            background: #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 1rem;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
        }
        .letter-card {
            max-width: 1000px;
            width: 100%;
            background: white;
            box-shadow: 0 20px 40px -12px rgba(0,0,0,0.25);
            border-radius: 1rem;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        /* ----- WATERMARK (logo image as background) ----- */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg) scale(1.8);
            opacity: 0.07;
            pointer-events: none;
            z-index: 0;
            user-select: none;
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
        }
        /* all content sits above watermark */
        .letter-content {
            position: relative;
            z-index: 2;
            padding: 1.5rem 2rem;
        }
        @media (max-width: 640px) {
            .letter-content { padding: 1rem 1.25rem; }
            .watermark { transform: translate(-50%, -50%) rotate(-25deg) scale(1.2); }
        }
        .letter-head {
            border-bottom: 2px solid #1e3a5f;
        }
        .board-line {
            border-top: 1px dashed #9ca3af;
            font-size: 0.7rem;
        }
        .signature {
            font-family: 'Courier New', monospace;
        }
        /* header logo image */
        .header-logo-img {
            height: 70px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }
        @media (max-width: 480px) {
            .header-logo-img { height: 50px; max-width: 130px; }
        }
        @media print {
            body { background: white; padding: 0.5in; }
            .letter-card { box-shadow: none; border-radius: 0; }
            .watermark { opacity: 0.1; }
        }
    </style>
</head>
<body>

<div class="letter-card">

    <!-- ===== WATERMARK (logo image) ===== -->
    <img 
        class="watermark" 
        src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRqka9HtHi5QpNxxBcGIcKb831huIiHmR-xx6e5NnE2X0T1uyXfp880DUg&s=10" 
        alt="Ghana Water Ltd. logo watermark"
        aria-hidden="true"
    >

    <!-- ===== MAIN CONTENT (above watermark) ===== -->
    <div class="letter-content">

        <!-- ===== HEADER: logo image + bankers/ref ===== -->
        <div class="letter-head pb-4 mb-4 flex flex-wrap justify-between items-center">
            <!-- Left: Logo image -->
            <div class="flex items-center gap-3 flex-1 min-w-[180px]">
                <img 
                    class="header-logo-img" 
                    src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRqka9HtHi5QpNxxBcGIcKb831huIiHmR-xx6e5NnE2X0T1uyXfp880DUg&s=10" 
                    alt="Ghana Water Ltd. Logo"
                >
                <div class="hidden sm:block h-12 w-0.5 bg-gray-300"></div>
                <div class="text-sm text-gray-600">
                    <span class="font-semibold text-blue-900">TEMA REGION</span>
                </div>
            </div>
            <!-- Right: Bankers & Ref -->
            <div class="text-right text-sm text-gray-700 mt-1 md:mt-0">
                <p><span class="font-semibold">Main Bankers:</span> Social Security Bank</p>
                <p>Ghana Commercial Bank</p>
                <p><span class="font-semibold">My Ref. No.:</span> RCM-02/Vol-1/01</p>
                <p><span class="font-semibold">Your Ref. No.:</span> ....................</p>
            </div>
        </div>

        <!-- ===== ADDRESS & DATE ===== -->
        <div class="flex flex-wrap justify-between text-sm text-gray-700 border-b border-gray-200 pb-2 mb-4">
            <div>
                <p><i class="fas fa-map-pin mr-1 text-blue-600"></i> Post Office Box 163, Tema – Ghana</p>
                <p><i class="fas fa-globe-africa mr-1 text-blue-600"></i> West Africa</p>
            </div>
            <div class="text-right">
                <p><i class="far fa-calendar-alt mr-1 text-blue-600"></i> 1/2 April, 2021</p>
            </div>
        </div>

        <!-- ===== ANNOUNCEMENT TITLE (VEHICLE MAINTENANCE) ===== -->
        <div class="mb-5">
            <h3 class="text-2xl font-bold text-blue-900 border-l-4 border-blue-600 pl-3 uppercase tracking-wide">
                <i class="fas fa-tools mr-2 text-blue-600"></i> VEHICLE MAINTENANCE NOTICE
            </h3>
            <p class="text-gray-600 mt-1 italic">
                Management of Ghana Water Company Limited – Tema Region, wishes to respectfully inform its cherished customers, 
                that there will be a <span class="font-semibold text-blue-800">scheduled vehicle service interruption</span> 
                from <span class="font-semibold">Tuesday, 20th – 23rd April, 2021</span> for routine maintenance works on 
                the <span class="font-semibold">42nd Transmission Pipeline</span> from Kpong Treatment Plant.
            </p>
        </div>

        <!-- ===== AFFECTED VEHICLES & SERVICE ZONES ===== -->
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
            <h4 class="text-md font-bold text-blue-800 uppercase tracking-wider flex items-center">
                <i class="fas fa-car-side mr-2 text-blue-700"></i> AFFECTED VEHICLES &amp; SERVICE ZONES
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-1 mt-2 text-sm">
                <span>• Tema Communities 1-12</span>
                <span>• Tema New Town</span>
                <span>• Tema Industrial</span>
                <span>• Kpone</span>
                <span>• Golf City</span>
                <span>• Bediako</span>
                <span>• Parts of VRA</span>
                <span>• Saki</span>
                <span>• Community 25</span>
                <span>• Tema General Hospital</span>
                <span>• Ashaiman Timber market</span>
                <span>• Tulaku</span>
                <span>• Bethlehem, Jericho, Lebanon</span>
                <span>• Sebrepor</span>
                <span>• Kakasunanka</span>
                <span>• Michel Camp</span>
                <span>• Boretyman, Santeo</span>
                <span>• Ashaiman Sun City</span>
                <span>• Ashaiman Township</span>
            </div>
            <p class="text-xs text-gray-500 mt-2 border-t border-blue-200 pt-1">
                <i class="fas fa-info-circle mr-1"></i> Fleet maintenance will affect service vehicles and utility transport in these areas.
            </p>
        </div>

        <!-- ===== ADVICE (fuel/service) ===== -->
        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6">
            <div class="flex items-start">
                <i class="fas fa-gas-pump text-yellow-700 text-xl mr-3 mt-1"></i>
                <div>
                    <p class="font-semibold text-yellow-800">ADVICE FOR CUSTOMERS</p>
                    <p class="text-gray-700 text-sm">
                        Customers in the affected areas are therefore being advised to 
                        <span class="font-bold text-yellow-900">ensure vehicles are fueled and serviced</span> 
                        before the interruption. 
                        <span class="block mt-1 text-gray-600">Supply (fuel &amp; maintenance support) will be restored as soon as the maintenance work is completed.</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- ===== REGRET & SIGNATURE ===== -->
        <div class="flex flex-wrap justify-between items-center border-t border-gray-200 pt-4 mt-2">
            <div>
                <p class="text-gray-700 text-sm"><i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i> Any inconvenience caused is deeply regretted.</p>
                <p class="text-gray-800 font-medium mt-1"><i class="fas fa-check-circle text-green-600 mr-1"></i> Thank you for your cooperation.</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-blue-900">Yours faithfully,</p>
                <p class="font-bold text-blue-800 text-lg signature tracking-wider">ING. MAC-DOE HANYABUI</p>
                <p class="text-sm text-gray-600 -mt-1">(AG. REGIONAL CHIEF MANAGER)</p>
            </div>
        </div>

        <!-- ===== BOARD OF DIRECTORS ===== -->
        <div class="board-line mt-6 pt-3 text-[0.65rem] text-gray-600">
            <p class="font-semibold text-gray-700">Board of Directors:</p>
            <p>
                Hoa. Alexander Kwamena Afenyi-Markin (Chairman), Dr. Clifford Briaumah (Managing Director), Mr. Joseph Obeng-Odo,
                Mr. Michael Ayensu, Naba Sirji Beving, Hon. Kwame Ampofo Tivunam, Clement Achekun, Kaba, Dr. Forster Kum-Atama Sarpong,
                Madam Maria Aha Lwolue-Johnson, Mr. Alexander K.B. Bumey, Mrs. Serena Kwakye Mintah
            </p>
        </div>

        <!-- ===== FOOTER: HEAD OFFICE & TEMA REGION ===== -->
        <div class="mt-4 pt-3 border-t border-gray-200 text-[0.6rem] text-gray-500 flex flex-wrap justify-between gap-2">
            <div>
                <p><span class="font-semibold text-gray-700">Head Office:</span> Registration office: 28th February Road Near Independence Square</p>
                <p>Tel. No. 233-6102-666781-7 233-634-390 Fax: 233-6302-663552</p>
                <p>Telephone: DIRWAT · Website: <a href="#" class="text-blue-600 hover:underline">www.gwcl.com.gh</a> · E-mail: info@gwcl.com.gh</p>
            </div>
            <div class="text-right">
                <p><span class="font-semibold text-gray-700">Tema Region:</span> Registration Office: Tema (Near Tema Labour Office)</p>
                <p>Tel. 233 (0) 303 202 832/3, Fax: 233 (0) 303 214</p>
                <p>Email: tema.region@ghanaawwater.info · Website: <a href="#" class="text-blue-600 hover:underline">www.ghanaawater.info</a></p>
            </div>
        </div>

        <!-- small watermark text -->
        <div class="mt-2 text-[0.5rem] text-gray-300 text-center border-t border-gray-100 pt-1">
            <i class="fas fa-tools mr-1"></i> vehicle maintenance edition · 2021
        </div>
    </div> <!-- /letter-content -->
</div> <!-- /letter-card -->

</body>
</html>