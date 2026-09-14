export const bangladeshDistricts = [
    'Bagerhat','Bandarban','Barguna','Barishal','Bhola','Bogura','Brahmanbaria','Chandpur','Chapainawabganj','Chattogram','Chuadanga','Cox’s Bazar','Cumilla','Dhaka','Dinajpur','Faridpur','Feni','Gaibandha','Gazipur','Gopalganj','Habiganj','Jamalpur','Jashore','Jhalokati','Jhenaidah','Joypurhat','Khagrachhari','Khulna','Kishoreganj','Kurigram','Kushtia','Lakshmipur','Lalmonirhat','Madaripur','Magura','Manikganj','Meherpur','Moulvibazar','Munshiganj','Mymensingh','Naogaon','Narail','Narayanganj','Narsingdi','Natore','Netrokona','Nilphamari','Noakhali','Pabna','Panchagarh','Patuakhali','Pirojpur','Rajbari','Rajshahi','Rangamati','Rangpur','Satkhira','Shariatpur','Sherpur','Sirajganj','Sunamganj','Sylhet','Tangail','Thakurgaon'
];

export const thanasByDistrict = {
    Dhaka: ['Adabor','Badda','Bangshal','Bimanbandar','Cantonment','Chak Bazar','Dakshinkhan','Darus Salam','Demra','Dhanmondi','Gandaria','Gulshan','Hazaribagh','Jatrabari','Kadamtali','Kafrul','Kalabagan','Kamrangirchar','Khilgaon','Khilkhet','Kotwali','Lalbagh','Mirpur','Mohammadpur','Motijheel','New Market','Pallabi','Paltan','Ramna','Rampura','Sabujbagh','Shah Ali','Shahbagh','Sher-e-Bangla Nagar','Shyampur','Sutrapur','Tejgaon','Tejgaon Industrial Area','Turag','Uttara East','Uttara West','Uttar Khan','Vatara','Wari'],
};

const dhakaAreas = ['dhaka','ঢাকা','uttara','উত্তরা','mirpur','মিরপুর','dhanmondi','ধানমন্ডি','gulshan','গুলশান','banani','বনানী','badda','বাড্ডা','mohakhali','মহাখালী','mohammadpur','মোহাম্মদপুর','motijheel','মতিঝিল','jatrabari','যাত্রাবাড়ী','bashundhara','বসুন্ধরা','rampura','রামপুরা','khilgaon','খিলগাঁও'];

export const detectDeliveryZone = ({ district = '', city = '', address = '' }) => {
    const exactDistrict = district.trim().toLowerCase();
    if (exactDistrict) return ['dhaka', 'ঢাকা'].includes(exactDistrict) ? 'inside_dhaka' : 'outside_dhaka';
    const location = `${city} ${address}`.toLowerCase();
    return dhakaAreas.some(area => location.includes(area)) ? 'inside_dhaka' : 'outside_dhaka';
};
