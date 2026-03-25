import{j as e,m as A}from"./vendor-motion-DQMF-AB3.js";import{r as a,L as p}from"./vendor-react-CY4oDSF8.js";import{u as q,c as d,R as u}from"./index-Bhg8UQGm.js";import{B as y}from"./Button-C1rQ7b2l.js";

const CATEGORIES=["SALUMI","FORMAGGI","VINI","OLIO","DOLCI","PANETTERIA","LEGUMI"];

const CATEGORY_COLORS={SALUMI:"bg-red-100 text-red-700",FORMAGGI:"bg-yellow-100 text-yellow-700",VINI:"bg-purple-100 text-purple-700",OLIO:"bg-green-100 text-green-700",DOLCI:"bg-pink-100 text-pink-700",PANETTERIA:"bg-amber-100 text-amber-700",LEGUMI:"bg-lime-100 text-lime-700"};

const PRICE_OPTIONS=[{val:null,text:"Tutti"},{val:10,text:"Fino a €10"},{val:25,text:"€10-25"},{val:50,text:"€25-50"},{val:9999,text:"Oltre €50"}];

function StarRating({rating=0,max=5}){return e.jsx("div",{className:"flex items-center gap-0.5",children:Array.from({length:max},(_,i)=>e.jsx("span",{className:i<Math.round(rating)?"text-amber-400":"text-stone-200",children:"★"},i))})}

function ProductCard({product,index=0}){const reduced=q();return e.jsx(A.article,{initial:reduced?void 0:{opacity:0,y:20},whileInView:reduced?void 0:{opacity:1,y:0},viewport:{once:!0,margin:"-50px"},transition:{duration:.4,delay:index*.05},className:"group",children:e.jsxs(p,{to:`${u.PRODUCTS}/${product.slug}`,className:"block glass-strong rounded-2xl overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-glass-hover focus-visible:outline-2 focus-visible:outline-ambra-500 focus-visible:outline-offset-2",children:[e.jsxs("div",{className:"relative aspect-[4/3] overflow-hidden bg-warm-100",children:[product.cover_image?e.jsx("img",{src:product.cover_image,alt:product.name,loading:"lazy",className:"w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"}):e.jsx("div",{className:"w-full h-full flex items-center justify-center text-6xl bg-warm-50",children:"🧀"}),e.jsx("div",{className:"absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent"}),product.category&&e.jsx("div",{className:"absolute top-3 left-3",children:e.jsx("span",{className:d("text-xs font-semibold px-2 py-1 rounded-full",CATEGORY_COLORS[product.category]||"bg-stone-100 text-stone-700"),children:product.category})})]}),e.jsxs("div",{className:"p-4",children:[e.jsx("h3",{className:"font-display text-lg font-bold text-warm-900 mb-1 group-hover:text-ambra-700 transition-colors line-clamp-1",children:product.name}),product.borough_name&&e.jsxs("p",{className:"text-xs text-warm-500 mb-2",children:["Da ",product.borough_name]}),e.jsxs("div",{className:"flex items-center justify-between",children:[product.rating?e.jsx(StarRating,{rating:product.rating}):e.jsx("span",{className:"text-xs text-warm-400",children:"Nessuna valutazione"}),product.price!=null&&e.jsxs("span",{className:"text-base font-bold text-ambra-700",children:["€",Number(product.price).toFixed(2)]})]})]})]})})}

function FP(){const reduced=q(),[products,setProducts]=a.useState([]),[loading,setLoading]=a.useState(!0),[search,setSearch]=a.useState(""),[category,setCategory]=a.useState(null),[borough,setBorough]=a.useState(null),[priceMax,setPriceMax]=a.useState(null);

a.useEffect(()=>{fetch("/api/v1/food_products.php").then(r=>r.json()).then(data=>{if(Array.isArray(data))setProducts(data);else if(data&&Array.isArray(data.data))setProducts(data.data);setLoading(!1)}).catch(()=>setLoading(!1))},[]);

const boroughs=a.useMemo(()=>{const seen=new Set();return products.reduce((acc,p)=>{if(p.borough_name&&!seen.has(p.borough_name)){seen.add(p.borough_name);acc.push({id:p.borough_id||p.borough_name,name:p.borough_name})}return acc},[]);},[products]);

const filtered=a.useMemo(()=>{let res=[...products];if(search.trim()){const q=search.toLowerCase();res=res.filter(p=>p.name?.toLowerCase().includes(q)||p.description?.toLowerCase().includes(q)||p.borough_name?.toLowerCase().includes(q))}if(category)res=res.filter(p=>p.category===category);if(borough)res=res.filter(p=>(p.borough_id||p.borough_name)===borough);if(priceMax!==null){res=res.filter(p=>{const price=Number(p.price||0);if(priceMax===10)return price<=10;if(priceMax===25)return price>10&&price<=25;if(priceMax===50)return price>25&&price<=50;return price>50})}return res},[products,search,category,borough,priceMax]);

const resetFilters=()=>{setSearch("");setCategory(null);setBorough(null);setPriceMax(null)};

if(loading)return e.jsx("div",{className:"min-h-screen flex items-center justify-center",children:e.jsx("img",{src:"/logo%20png.png",alt:"Caricamento...",className:"animate-spin h-12 w-12"})});

return e.jsxs("main",{id:"main-content",className:"min-h-screen pt-20 pb-16",children:[
  e.jsxs("section",{className:"relative h-64 md:h-80 overflow-hidden",children:[
    e.jsx("div",{className:"absolute inset-0 bg-gradient-to-br from-amber-900 via-amber-700 to-amber-500"}),
    e.jsx("div",{className:"absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"}),
    e.jsxs("div",{className:"relative h-full flex flex-col justify-end max-w-7xl mx-auto px-4 pb-8",children:[
      e.jsx(A.h1,{initial:reduced?void 0:{opacity:0,y:20},animate:{opacity:1,y:0},transition:{duration:.5},className:"font-display text-3xl md:text-5xl font-bold text-white",children:"Prodotti Tipici"}),
      e.jsx(A.p,{initial:reduced?void 0:{opacity:0,y:20},animate:{opacity:1,y:0},transition:{duration:.5,delay:.1},className:"text-white/80 mt-2 text-sm md:text-base max-w-xl",children:"Salumi, formaggi, vini, oli e dolci dell'Alta Irpinia. Eccellenze del territorio a chilometro zero."})
    ]})
  ]}),
  e.jsxs("div",{className:"max-w-7xl mx-auto px-4 py-8",children:[
    e.jsxs("div",{className:"flex flex-col lg:flex-row gap-8",children:[
      e.jsxs("aside",{className:"lg:w-64 flex-shrink-0 space-y-6",children:[
        e.jsxs("div",{className:"glass-strong rounded-2xl p-4",children:[
          e.jsx("h2",{className:"font-semibold text-warm-900 mb-3 text-sm uppercase tracking-wide",children:"Cerca"}),
          e.jsx("input",{type:"search",placeholder:"Cerca prodotti...",value:search,onChange:ev=>setSearch(ev.target.value),className:"w-full px-3 py-2 rounded-xl border border-stone-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-ambra-400","aria-label":"Cerca prodotti"})
        ]}),
        e.jsxs("div",{className:"glass-strong rounded-2xl p-4",children:[
          e.jsx("h2",{className:"font-semibold text-warm-900 mb-3 text-sm uppercase tracking-wide",children:"Categoria"}),
          e.jsxs("div",{className:"space-y-1",children:[
            e.jsx("button",{onClick:()=>setCategory(null),className:d("w-full text-left text-sm px-3 py-2 rounded-lg transition-colors",category===null?"bg-ambra-600 text-white":"text-warm-700 hover:bg-warm-100"),children:"Tutte le categorie"}),
            CATEGORIES.map(cat=>e.jsx("button",{key:cat,onClick:()=>setCategory(category===cat?null:cat),className:d("w-full text-left text-sm px-3 py-2 rounded-lg transition-colors",category===cat?"bg-ambra-600 text-white":"text-warm-700 hover:bg-warm-100"),children:cat}))
          ]})
        ]}),
        boroughs.length>0&&e.jsxs("div",{className:"glass-strong rounded-2xl p-4",children:[
          e.jsx("h2",{className:"font-semibold text-warm-900 mb-3 text-sm uppercase tracking-wide",children:"Borgo"}),
          e.jsxs("div",{className:"space-y-1",children:[
            e.jsx("button",{onClick:()=>setBorough(null),className:d("w-full text-left text-sm px-3 py-2 rounded-lg transition-colors",borough===null?"bg-ambra-600 text-white":"text-warm-700 hover:bg-warm-100"),children:"Tutti i borghi"}),
            boroughs.map(b=>e.jsx("button",{key:b.id,onClick:()=>setBorough(borough===b.id?null:b.id),className:d("w-full text-left text-sm px-3 py-2 rounded-lg transition-colors",borough===b.id?"bg-ambra-600 text-white":"text-warm-700 hover:bg-warm-100"),children:b.name}))
          ]})
        ]}),
        e.jsxs("div",{className:"glass-strong rounded-2xl p-4",children:[
          e.jsx("h2",{className:"font-semibold text-warm-900 mb-3 text-sm uppercase tracking-wide",children:"Fascia prezzo"}),
          e.jsx("div",{className:"space-y-1",children:PRICE_OPTIONS.map(opt=>e.jsx("button",{key:String(opt.val),onClick:()=>setPriceMax(priceMax===opt.val?null:opt.val),className:d("w-full text-left text-sm px-3 py-2 rounded-lg transition-colors",priceMax===opt.val?"bg-ambra-600 text-white":"text-warm-700 hover:bg-warm-100"),children:opt.text}))})
        ]}),
        (search||category||borough||priceMax!==null)&&e.jsx("button",{onClick:resetFilters,className:"w-full text-sm text-ambra-600 hover:text-ambra-700 font-medium underline",children:"Reset filtri"})
      ]}),
      e.jsxs("div",{className:"flex-1",children:[
        e.jsxs("div",{className:"flex items-center justify-between mb-6",children:[
          e.jsxs("p",{className:"text-sm text-warm-600",children:[filtered.length," prodott",filtered.length===1?"o":"i"," trovati"]}),
          (search||category||borough||priceMax!==null)&&e.jsx("button",{onClick:resetFilters,className:"text-sm text-ambra-600 hover:text-ambra-700 font-medium underline",children:"Azzera filtri"})
        ]}),
        filtered.length===0?e.jsxs("div",{className:"text-center py-20",children:[
          e.jsx("p",{className:"text-6xl mb-4",children:"🧀"}),
          e.jsx("p",{className:"text-warm-600 font-medium",children:"Nessun prodotto trovato"}),
          e.jsx("p",{className:"text-sm text-warm-500 mt-1",children:"Prova a modificare i filtri o la ricerca"}),
          e.jsx("button",{onClick:resetFilters,className:"mt-4 text-sm text-ambra-600 hover:text-ambra-700 font-medium underline",children:"Resetta tutti i filtri"})
        ]}):e.jsx("div",{className:"grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6",children:filtered.map((prod,idx)=>e.jsx(ProductCard,{product:prod,index:idx},prod.id||prod.slug||idx))})
      ]})
    ]])
  }])
]})}

export{FP as default};
