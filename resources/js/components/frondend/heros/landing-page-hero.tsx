import {url} from "@/helpers/general";


const LandingPageHero = () => {
  return (
    <div className="d-flex flex-column flex-center w-100 min-h-350px min-h-lg-500px px-9">

      <div className="text-center mb-5 mb-lg-10 py-10 py-lg-20">

        <h1 className="text-white lh-base fw-bold fs-2x fs-lg-3x mb-15">Build An Outstanding Solutions
          <br/>with
          <span style={{
            "background": "linear-gradient(to right, #12CE5D 0%, #FFD80C 100%)",
            "WebkitBackgroundClip": "text",
            "WebkitTextFillColor": "transparent"
          }}>
                  <span id="kt_landing_hero_text">The Best Theme Ever</span>
                </span>
        </h1>

        <a className="btn btn-primary" href="index.html">Try Metronic</a>
      </div>

      <div className="d-flex flex-center flex-wrap position-relative px-5">

        <div className="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Fujifilm">
          <img alt="" className="mh-30px mh-lg-40px" src={url('media/svg/brand-logos/fujifilm.svg')}/>
        </div>


        <div className="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Vodafone">
          <img alt="" className="mh-30px mh-lg-40px" src={url('media/svg/brand-logos/vodafone.svg')}/>
        </div>


        <div className="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="KPMG International">
          <img alt="" className="mh-30px mh-lg-40px" src={url("media/svg/brand-logos/kpmg.svg")}/>
        </div>


        <div className="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Nasa">
          <img alt="" className="mh-30px mh-lg-40px" src={url("media/svg/brand-logos/nasa.svg")}/>
        </div>


        <div className="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Aspnetzero">
          <img alt="" className="mh-30px mh-lg-40px" src={url("media/svg/brand-logos/aspnetzero.svg")}/>
        </div>


        <div className="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="AON - Empower Results">
          <img alt="" className="mh-30px mh-lg-40px" src={url('"media/svg/brand-logos/aon.svg"')}/>
        </div>


        <div className="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Hewlett-Packard">
          <img alt="" className="mh-30px mh-lg-40px" src={url("media/svg/brand-logos/hp-3.svg")}/>
        </div>


        <div className="d-flex flex-center m-3 m-md-6" data-bs-toggle="tooltip" title="Truman">
          <img alt="" className="mh-30px mh-lg-40px" src={url("media/svg/brand-logos/truman.svg")}/>
        </div>

      </div>

    </div>
  )
}

export  default  LandingPageHero;
